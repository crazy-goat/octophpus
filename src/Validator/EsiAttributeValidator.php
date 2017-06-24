<?php

namespace CrazyGoat\Octophpus\Validator;

use Nunzion\Expect;

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
        Expect::that($this->attributes)->itsArrayElement('SRC')->isString()->isNotEmpty();
        Expect::that($this->attributes)->itsArrayElement('TIMEOUT')->isUndefinedOrFloat();
        if (isset($this->attributes['TIMEOUT'])) {
            Expect::that($this->attributes)->itsArrayElement('TIMEOUT')->isFloat()->isGreaterThan(0);
        }

        return true;
    }
}