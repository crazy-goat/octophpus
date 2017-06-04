<?php

namespace CrazyGoat\Octophpus;

use CrazyGoat\Octophpus\Validator\OptionsValidator;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Mantle implements LoggerAwareInterface
{
    const ON_REJECT_EMPTY = 'empty';
    const ON_REJECT_EXCEPTION = 'exception';

    use LoggerAwareTrait;

    /**
     * @var array options
     */
    private $options;

    /**
     * @var CacheItemPoolInterface
     */
    private $cachePool = null;

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
    }

    public function setCache(?CacheItemPoolInterface $cachePool) : Mantle
    {
        $this->cachePool = $cachePool;
        return $this;
    }

    public function decorate(string $data): string
    {
        if (preg_match_all('/<esi:include [^>]*\\/>/ims', $data, $matches)) {

            /** @var Client $client */
            $client = new Client($this->clientOptions());

            /** @var EsiRequest[] $esiRequests */
            $esiRequests = $this->makeEsiRequests($matches[0]);

            (new EachPromise(
                $this->createRequestPromises()($client, $esiRequests),
                [
                    'concurrency' => $this->options['concurrency'],
                    'fulfilled' => $this->handleFulfilled($data, $esiRequests),
                    'rejected' => $this->handleRejected($data, $esiRequests)
                ]
            ))->promise()->wait();

        } else {
            $this->logger->info('No esi tags found');
        }

        return $data;
    }

    private function handleFulfilled(string &$data, array $esiRequests)
    {
        return (function (Response $response, int $index) use (&$data, $esiRequests)
        {
            $needle = $esiRequests[$index]->getEsiTag();
            $pos = strpos($data, $needle);
            if ($pos !== false) {
                $data = substr_replace($data, $response->getBody()->getContents(), $pos, strlen($needle));
            } else {
                $this->logger->error('This should not happen. Could not replace previously found esi tag.');
            }
        });
    }

    private function handleRejected(string &$data, array $esiRequests) {
        if ($this->options['on_reject'] instanceof \Closure) {
            return $this->options['on_reject'];
        }

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

    private function createRequestPromises()
    {
        return (function (Client $client, array $esiRequests) {
            /** @var EsiRequest $esiRequest */
            foreach ($esiRequests as $esiRequest) {
                yield $client->requestAsync(
                    'GET',
                    $esiRequest->getSrc(),
                    array_merge($this->requestOptions(), $esiRequest->requestOptions())
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
            'base_uri' => ''
        ];
    }

    private function makeEsiRequests(array $esiTags) : array
    {
        $ret = [];
        foreach ($esiTags as $esiTag) {
             $ret[] = new EsiRequest($esiTag);
        }
        return $ret;
    }

    /**
     * @param array $options
     * @return Mantle
     */
    public function setOptions(array $options): Mantle
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

    private function requestOptions() : array
    {
        return [];
    }

    private function clientOptions() : array
    {
        return [
            'concurrency' => $this->options['concurrency'],
            'timeout' => $this->options['timeout'],
            'base_uri' => $this->options['base_uri']
        ];
    }
}