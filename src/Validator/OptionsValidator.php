<?php

namespace CrazyGoat\Octophpus\Validator;

use CrazyGoat\Octophpus\Exception\InvalidOptionValueException;
use CrazyGoat\Octophpus\EsiTentacles;
use Nunzion\Expect;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class OptionsValidator implements ValidatorInterface
{
    use Camelize;
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return bool
     * @throws InvalidOptionValueException
     * @uses validateConcurrency
     * @uses validateTimeout
     * @uses validateCachePool
     * @uses validateLogger
     * @uses validateBaseUri
     * @uses validateOnReject
     * @uses validateOnTimeout
     */
    public function validate(): bool
    {
        foreach ($this->config as $key => $value) {
            $function = 'validate'.$this->camlize($key);
            if (method_exists($this, $function)) {
                $this->$function($value);
            }
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

    private function validateConcurrency(int $value) : void
    {
        Expect::that($value)->isInt()->isGreaterThan(1);
    }

    private function validateTimeout(float $value) : void
    {
        Expect::that($value)->isFloat()->isGreaterThan(0);
    }

    private function validateCachePool(?CacheItemPoolInterface $value) : void
    {
        Expect::that($value)->isNullOrInstanceOf('Psr\Cache\CacheItemPoolInterface');
    }

    private function validateLogger(?LoggerInterface $value) : void
    {
        Expect::that($value)->isNullOrInstanceOf('sr\Log\LoggerInterface');
    }

    private function validateBaseUri(string $value) : void
    {
        Expect::that($value)->isString();
    }

    private function validateOnReject($value) : void
    {
        if (is_string($this->config['on_reject']) && !in_array($this->config['on_reject'], [
                EsiTentacles::ON_REJECT_EXCEPTION,
                EsiTentacles::ON_REJECT_EMPTY
            ])) {
            throw new InvalidOptionValueException(
                'Invalid on_reject option, valid values: '.
                EsiTentacles::ON_REJECT_EXCEPTION.', '.EsiTentacles::ON_REJECT_EMPTY.' or Closure'
            );
        } else {
            Expect::that($value)->isInstanceOf('Closure');
        }
    }

    private function validateOnTimeout(string $value) : void
    {
        Expect::that($value)->isString()->isNotEmpty();
        if (!in_array($value, [EsiTentacles::ON_TIMEOUT_EXCEPTION, EsiTentacles::ON_TIMEOUT_H_INCLUDE])) {
            throw new InvalidOptionValueException(
                'Invalid on_reject option, valid values: '.
                EsiTentacles::ON_TIMEOUT_EXCEPTION.', '.EsiTentacles::ON_TIMEOUT_H_INCLUDE
            );
        }
    }
}