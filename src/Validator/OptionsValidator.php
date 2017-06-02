<?php

namespace CrazyGoat\Octophpus\Validator;

use CrazyGoat\Octophpus\Exception\InvalidOptionValueException;
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

        return true;
    }
}