<?php

namespace CrazyGoat\Octophpus\Validator;

use CrazyGoat\Octophpus\Exception\EsiTagParseException;

class EsiAttributeValidator implements ValidatorInterface
{
    /**
     * @var array
     */
    private $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function validate(): bool
    {
        if (empty($this->attributes['SRC'])) {
            throw new EsiTagParseException('Esi tag has empty required parameter src');
        }

        if (isset($this->attributes['TIMEOUT'])) {
            if ((float)$this->attributes['TIMEOUT']<=0) {
                throw new EsiTagParseException('Timeout must be greater then 0.');
            }
        }

        return true;
    }
}