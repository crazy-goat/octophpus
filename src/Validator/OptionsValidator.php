<?php

namespace CrazyGoat\Octophpus\Validator;

use CrazyGoat\Octophpus\Exception\InvalidOptionValueException;

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
        if (isset($this->config['concurrency']) && (int)$this->config['concurrency'] < 1) {
            throw new InvalidOptionValueException('Concurrency must be greater than 0.');
        }

        if (isset($this->config['timeout']) && (int)$this->config['timeout'] <=0 ) {
            throw new InvalidOptionValueException('Timeout option must greater than 0.');
        }

        return true;
    }
}