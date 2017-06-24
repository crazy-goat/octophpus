<?php

namespace CrazyGoat\Octophpus;

use CrazyGoat\Octophpus\Validator\EsiAttributeValidator;
use PHPUnit\Framework\Error\Error;
use PHPUnit\Framework\TestCase;

class EsiAttributeValidatorTest extends TestCase
{


    /**
     * @param $value
     * @expectedException Error
     * @dataProvider constructorDataProvider
     */
    public function testInvalidConstructor($value)
    {
        new EsiAttributeValidator($value);
    }

    /**
     * @param $value
     * @dataProvider invalidDataProvider
     * @expectedException \Exception
     */
    public function testInvalidValue($value)
    {
        (new EsiAttributeValidator($value))->validate();
    }

    /**
     * @param $value
     * @dataProvider validDataProvider
     */
    public function testValidValue($value)
    {
        $this->assertTrue((new EsiAttributeValidator($value))->validate());
    }

    public function constructorDataProvider()
    {
        return [
            [null],
            [1],
            ['string'],
            [new \stdClass()],
            [function(){}]
        ];
    }

    public function invalidDataProvider()
    {
        return [
            [[]],
            [['SRC' => null,]],
            [['SRC' => '',]],
            [['TIMEOUT' => 5.0]],
            [['SRC' => 'some_url', 'TIMEOUT' => null]],
            [['SRC' => 'some_url', 'TIMEOUT' => -1.0]],
            [['SRC' => 'some_url', 'TIMEOUT' => 0.0]],
            [['SRC' => 'some_url', 'TIMEOUT' => '5.0']],
        ];
    }

    public function validDataProvider()
    {
        return [
            [['SRC' => 'some_url']],
            [['SRC' => 'some_url', 'TIMEOUT' => 5.0]],
        ];
    }
}
