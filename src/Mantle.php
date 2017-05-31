<?php

namespace CrazyGoat\Octophpus;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class Mantle
{
    /**
     * @var array options
     */
    private $options;

    /**
     * @var LoggerInterface
     */
    private $logger = null;

    /**
     * @var CacheItemPoolInterface
     */
    private $cachePool = null;

    /**
     * @var Client
     */
    private $client;

    /**
     * Mantle constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->defaultOptions(), $options);
        $this->client = new Client($this->options['guzzle']);
    }

    public function setLogger(?LoggerInterface $logger): Mantle
    {
        $this->logger = $logger;
        return $this;
    }

    public function setCache(?CacheItemPoolInterface $cachePool) : Mantle
    {
        $this->cachePool = $cachePool;
        return $this;
    }

    public function decorate(string $data): string
    {
        if (preg_match_all('/<esi:include [^>]*src=\\"(?P<src>.+)\\"[^>]*\\/>/i', $data, $matches)) {

            $requests = function ($matchesSrc) {
                foreach ($matchesSrc as $key => $match) {
                    yield new Request('GET', $match);
                }
            };

            $pool = new Pool($this->client, $requests($matches['src']), [
                'concurrency' => $this->options['guzzle']['concurrency']
                    ?? $this->defaultOptions()['guzzle']['concurrency'],
                'fulfilled' => function (Response $response, int $index) use (&$data, $matches) {
                    $data = str_replace($matches[$index], $response->getBody()->getContents(), $data);
                },
                'rejected' => function (\Exception $reason, int $index) use (&$data, $matches) {
                    $data = str_replace($matches[$index], '', $data);
                },
            ]);
            $pool->promise()->wait();
        }

        return $data;
    }

    private function defaultOptions(): array
    {
        return [
            'guzzle' => [
                'concurrency' => 5
            ]
        ];
    }

    /**
     * @param array $options
     * @return Mantle
     */
    public function setOptions(array $options): Mantle
    {
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
}