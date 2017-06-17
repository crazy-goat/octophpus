<?php

namespace CrazyGoat\Octophpus;

use CrazyGoat\Octophpus\Validator\OptionsValidator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class EsiTentacles
{
    const ON_REJECT_EMPTY = 'empty';
    const ON_REJECT_EXCEPTION = 'exception';
    const ON_TIMEOUT_H_INCLUDE = 'hinclude';
    const ON_TIMEOUT_EXCEPTION = 'exception';

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
        $this->logger = $this->options['logger'];
        $this->cachePool = $this->options['cache_pool'];
    }

    public function decorate(string $data): string
    {
        $parser = new EsiParser();

        $recurrency = $this->options['recurecny_level'];

        while ($parser->parse($data) && $recurrency > 0) {

            /** @var EsiRequest[] $esiRequests */
            $esiRequests = $parser->esiRequests();

            $work = new EachPromise(
                $this->createRequestPromises()($esiRequests),
                [
                    'concurrency' => $this->options['concurrency'],
                    'fulfilled' => $this->options['fulfilled']($data, $esiRequests),
                    'rejected' => $this->options['rejected']($data, $esiRequests)
                ]
            );
            $work->promise()->wait();
            $recurrency--;
        }

        return $data;
    }

    private function createRequestPromises(): \Closure
    {
        return (function (array $esiRequests) {
            $client = new Client($this->clientOptions());
            /** @var EsiRequest $esiRequest */
            foreach ($esiRequests as $esiRequest) {
                yield $this->createSingleRequest($esiRequest, $client);
            }
        });
    }

    private function createSingleRequest(EsiRequest $esiRequest, Client $client)
    {
        $cacheKey = $this->options['cache_prefix'] . ':' . base64_encode($esiRequest->getSrc());

        if ($this->cachePool instanceof CacheItemPoolInterface && $this->cachePool->hasItem($cacheKey)) {
            yield new Response(200, [], $this->cachePool->getItem($cacheKey)->get());
        }

        yield $client->requestAsync(
            'GET',
            $esiRequest->getSrc(),
            array_merge($this->options['request_options'], $esiRequest->requestOptions())
        );
    }

    private function defaultOptions(): array
    {
        return [
            'concurrency' => 5,
            'timeout' => 2.0,
            'on_reject' => static::ON_REJECT_EXCEPTION,
            'on_timeout' => static::ON_TIMEOUT_EXCEPTION,
            'base_uri' => '',
            'cache_prefix' => 'esi:include',
            'cache_ttl' => 3600,
            'request_options' => [],
            'recurecny_level' => 1,
            'cache_pool' => null,
            'logger' => new VoidLogger(),
            'fulfilled' => $this->defaultFulfilled(),
            'rejected' => $this->defaultReject()
        ];
    }

    private function defaultFulfilled(): \Closure
    {
        return function (string &$data, array $esiRequests) {
            return (function (Response $response, int $index) use (&$data, $esiRequests) {
                /** @var EsiRequest $esiRequest */
                $esiRequest = $esiRequests[$index];
                $needle = $esiRequest->getEsiTag();
                $pos = strpos($data, $needle);
                if ($pos !== false) {
                    $contents = $response->getBody()->getContents();
                    $this->setCache($esiRequest, $contents);
                    $data = substr_replace($data, $contents, $pos, strlen($needle));
                } else {
                    $this->logger->error('This should not happen. Could not replace previously found esi tag.');
                }
            });
        };
    }

    private function setCache(EsiRequest $esiRequest, string $content)
    {
        if ($this->cachePool instanceof CacheItemPoolInterface && !$esiRequest->noCache()) {
            $cacheKey = $this->options['cache_prefix'] . ':' . base64_encode($esiRequest->getSrc());

            $this->cachePool->save(
                $this->cachePool
                    ->getItem($cacheKey)
                    ->set($content)
                    ->expiresAfter($this->options['cache_ttl'])
            );
        }
    }

    private function defaultReject(): \Closure
    {
        return function (string &$data, array $esiRequests) {
            return (function (\Exception $reason, int $index) use (&$data, $esiRequests) {
                /** @var EsiRequest $esiRequest */
                $esiRequest = $esiRequests[$index];

                $this->logger->error(
                    'Could not fetch [' . $esiRequest->getSrc() . ']. Reason: ' . $reason->getMessage()
                );
                if ($reason instanceof ConnectException &&
                    $this->options['on_timeout'] == static::ON_TIMEOUT_H_INCLUDE
                ) {
                    $data = str_replace(
                        $esiRequest->getEsiTag(),
                        '<hx:include src="' . $this->options['base_uri'] . $esiRequest->getSrc() . '"></hx:include>',
                        $data
                    );
                    return;
                }

                if ($this->options['on_reject'] == static::ON_REJECT_EMPTY) {
                    $data = str_replace($esiRequest->getEsiTag(), '', $data);
                } else {
                    throw $reason;
                }
            });
        };
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

    private function clientOptions(): array
    {
        return [
            'concurrency' => $this->options['concurrency'],
            'timeout' => $this->options['timeout'],
            'base_uri' => $this->options['base_uri'],
        ];
    }
}