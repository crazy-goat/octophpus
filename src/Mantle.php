<?php

namespace CrazyGoat\Octophpus;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Mantle implements LoggerAwareInterface
{
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
        $this->logger = new VoidLogger();
    }

    public function setCache(?CacheItemPoolInterface $cachePool) : Mantle
    {
        $this->cachePool = $cachePool;
        return $this;
    }

    public function decorate(string $data): string
    {
        if (preg_match_all('/<esi:include [^>]*\\/>/ims', $data, $matches)) {

            $pool = new Pool($this->client, $this->makeRequests()($matches[0]), [
                'concurrency' => $this->options['guzzle']['concurrency']
                    ?? $this->defaultOptions()['guzzle']['concurrency'],
                'fulfilled' => function (Response $response, int $index) use (&$data, $matches) {

                    $needle = $matches[0][$index];
                    $pos = strpos($data, $needle);
                    if ($pos !== false) {
                        $data = substr_replace($data, $response->getBody()->getContents(), $pos, strlen($needle));
                    } else {
                        $this->logger->error('This should not happen. Could not replace previously found esi tag.');
                    }
                },
                'rejected' => function (\Exception $reason, int $index) use (&$data, $matches) {

                    $this->logger->error(
                        'Could not fetch ['.$matches['src'][$index].']. Reason: '.$reason->getMessage()
                    );

                    $data = str_replace($matches[$index], '', $data);
                },
            ]);
            $pool->promise()->wait();
        } else {
            $this->logger->info('No esi tags found');
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

    private function makeRequests() {
        return function (array $matches) {
            foreach ($matches as $match) {
                $esiParser = new EsiParser($match);
                yield new Request('GET', $esiParser->getSrc());
            }
        };
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