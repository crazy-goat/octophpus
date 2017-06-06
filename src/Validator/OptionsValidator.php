<?php

namespace CrazyGoat\Octophpus\Validator;

use CrazyGoat\Octophpus\Exception\InvalidOptionValueException;
use CrazyGoat\Octophpus\EsiTentacles;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class OptionsValidator implements ValidatorInterface
{

    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function validate(): bool
    {
        if (array_key_exists('concurrency', $this->config) && (int)$this->config['concurrency'] < 1) {
            throw new InvalidOptionValueException('Concurrency must be greater than 0.');
        }

        if (array_key_exists('timeout', $this->config) && (int)$this->config['timeout'] <=0 ) {
            throw new InvalidOptionValueException('Timeout option must greater than 0.');
        }

        if (array_key_exists('logger', $this->config) && !($this->config['logger'] instanceof LoggerInterface)) {
            throw new InvalidOptionValueException('Logger must be instance of LoggerInterface');
        }

        if (array_key_exists('cachePool', $this->config)
            && !($this->config['cachePool'] instanceof CacheItemPoolInterface)
        ) {
            throw new InvalidOptionValueException('CachePool must be instance of CacheItemPoolInterface');
        }

        if (array_key_exists('base_uri', $this->config) && !is_string($this->config['base_uri'])) {
            throw new InvalidOptionValueException('Base URI option must be a string');
        }

        if (array_key_exists('on_reject', $this->config)) {
            if (is_string($this->config['on_reject']) && !in_array($this->config['on_reject'], [
                    EsiTentacles::ON_REJECT_EXCEPTION,
                    EsiTentacles::ON_REJECT_EMPTY
                ])) {
                throw new InvalidOptionValueException(
                    'Invalid on_reject option, valid values: '.
                    EsiTentacles::ON_REJECT_EXCEPTION.', '.EsiTentacles::ON_REJECT_EMPTY.' or Closure'
                );
            } else {
                if (!is_string($this->config['on_reject']) && !($this->config['on_reject'] instanceof \Closure)) {
                    throw new InvalidOptionValueException(
                        'Invalid on_reject option, expected Closure got: '.gettype($this->config['on_reject'])
                    );
                }
            }

        }

        return true;
    }
}