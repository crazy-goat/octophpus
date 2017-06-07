<?php

namespace CrazyGoat\Octophpus;

use CrazyGoat\Octophpus\Validator\OptionsValidator;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class EsiTentacles
{
    const ON_REJECT_EMPTY = 'empty';
    const ON_REJECT_EXCEPTION = 'exception';

    /**
     * @var array options
     */
    private $options;

    /**
     * @var CacheItemPoolInterface
     */
    private $cachePool = null;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Mantle constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $validator = new OptionsValidator($options);
        $validator->validate();

        $this->options = array_merge($this->defaultOptions(), $options);
        $this->logger = (isset($options['logger'])) ? $options['logger'] : new VoidLogger();
        $this->cachePool = (isset($options['cachePool'])) ? $options['cachePool'] : null;
    }

    public function decorate(string $data): string
    {
        $parser = new EsiParser();

        if ($parser->parse($data)) {

            /** @var EsiRequest[] $esiRequests */
            $esiRequests = $parser->esiRequests();

            (new EachPromise(
                $this->createRequestPromises()($esiRequests),
                [
                    'concurrency' => $this->options['concurrency'],
                    'fulfilled' => $this->options['fulfilled']($data, $esiRequests),
                    'rejected' =>  $this->options['rejected']($data, $esiRequests)
                ]
            ))->promise()->wait();

        } else {
            $this->logger->info('No esi:include tag found');
        }

        return $data;
    }

    private function createRequestPromises()
    {
        return (function (array $esiRequests) {
            $client = new Client($this->clientOptions());
            /** @var EsiRequest $esiRequest */
            foreach ($esiRequests as $esiRequest) {

                $cacheKey = $this->options['cache_prefix'].':'.base64_encode($esiRequest->getSrc());

                if ($this->cachePool instanceof CacheItemPoolInterface &&
                    $this->cachePool->hasItem($cacheKey)
                ) {
                    $value = $this->cachePool->getItem($cacheKey)->get();
                    yield new Response(200, [], $value);
                    continue;
                }

                yield $client->requestAsync(
                    'GET',
                    $esiRequest->getSrc(),
                    array_merge($this->options['request_options'], $esiRequest->requestOptions())
                );
            }
        });
    }

    private function defaultOptions(): array
    {
        return [
            'concurrency' => 5,
            'timeout' => 2.0,
            'on_reject' => static::ON_REJECT_EXCEPTION,
            'base_uri' => '',
            'cache_prefix' => 'esi:include',
            'cache_ttl' => 3600,
            'request_options' => [],
            'fulfilled' => function (string &$data, array $esiRequests)
            {
                return (function (Response $response, int $index) use (&$data, $esiRequests)
                {
                    /** @var EsiRequest $esiRequest */
                    $esiRequest = $esiRequests[$index];
                    $needle = $esiRequest->getEsiTag();
                    $pos = strpos($data, $needle);
                    if ($pos !== false) {

                        if ($this->cachePool instanceof CacheItemPoolInterface && !$esiRequest->isNoCache()) {
                            $cacheKey = $this->options['cache_prefix'].':'.base64_encode($esiRequest->getSrc());

                            $this->cachePool->save(
                                $this->cachePool
                                    ->getItem($cacheKey)
                                    ->set($response->getBody()->getContents())
                                    ->expiresAfter($this->options['cache_ttl'])
                            );
                        }

                        $data = substr_replace($data, $response->getBody()->getContents(), $pos, strlen($needle));
                    } else {
                        $this->logger->error('This should not happen. Could not replace previously found esi tag.');
                    }
                });
            },
            'rejected' => function (string &$data, array $esiRequests) {
                return (function (\Exception $reason, int $index) use (&$data, $esiRequests) {

                    $this->logger->error(
                        'Could not fetch ['.$esiRequests[$index]->getSrc().']. Reason: '.$reason->getMessage()
                    );

                    if ($this->options['on_reject'] == static::ON_REJECT_EMPTY) {
                        $data = str_replace($esiRequests[$index]->getEsiTag(), '', $data);
                    } else {
                        throw $reason;
                    }
                });
            }
        ];
    }

    /**
     * @param array $options
     * @return EsiTentacles
     */
    public function setOptions(array $options): EsiTentacles
    {
        $validator = new OptionsValidator($options);
        $validator->validate();

        $this->options = $options;
        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    private function clientOptions() : array
    {
        return [
            'concurrency' => $this->options['concurrency'],
            'timeout' => $this->options['timeout'],
            'base_uri' => $this->options['base_uri'],
        ];
    }
}