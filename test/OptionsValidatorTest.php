<?php

namespace CrazyGoat\Octophpus;

use CrazyGoat\Octophpus\Validator\Camelize;
use CrazyGoat\Octophpus\Validator\OptionsValidator;
use PHPUnit\Framework\Error\Error;
use PHPUnit\Framework\TestCase;
use Stash\Driver\Ephemeral;
use Stash\Pool;

class OptionsValidatorTest extends TestCase
{
    use Camelize;

    /**
     * @expectedException Error
     * @dataProvider invalidDataTypeProvider
     */
    public function testInvalidType($key, $value)
    {
        $validator = new OptionsValidator([$key => $value]);
        $validator->validate();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidDataValueProvider
     */
    public function testInvalidValue($key, $value)
    {
        $validator = new OptionsValidator([$key => $value]);
        $validator->validate();
    }

    /**
     * @dataProvider validDataProvider
     * @param $value
     */
    public function testValidTimeout($key, $value)
    {
        $validator = new OptionsValidator([$key => $value]);
        $this->assertTrue($validator->validate());
    }

    public function testDefaultOptions()
    {
        $defaultOptions = (new EsiTentacles())->getOptions();
        $validator = new OptionsValidator($defaultOptions);
        $this->assertTrue($validator->validate());
    }

    public function testAllOptionsHasValidator()
    {
        $reflection = new \ReflectionClass('CrazyGoat\Octophpus\Validator\OptionsValidator');
        $options = array_keys((new EsiTentacles())->getOptions());
        foreach ($options as $key) {
            $function = 'validate'.$this->camlize($key);
            $this->assertTrue($reflection->hasMethod($function), 'No validator for key: '.$key);
        }
    }

    public function invalidDataTypeProvider()
    {
        return [
            ['concurrency', null],
            ['concurrency', 'string'],
            ['concurrency', []],
            ['concurrency', new \stdClass()],

            ['timeout', null],
            ['timeout', 'string'],
            ['timeout', []],
            ['timeout', new \stdClass()],

            ['request_options', null],
            ['request_options', 'string'],
            ['request_options', 1],
            ['request_options', new \stdClass()],

            ['cache_prefix', null],
            ['cache_prefix', []],
            ['cache_prefix', new \stdClass()],

            ['cache_pool', 'string'],
            ['cache_pool', []],
            ['cache_pool', new \stdClass()],
            ['cache_pool', 1],

            ['logger', 'string'],
            ['logger', []],
            ['logger', new \stdClass()],
            ['logger', 1],

            ['base_uri', null],
            ['base_uri', []],
            ['base_uri', new \stdClass()],

            ['on_timeout', null],
            ['on_timeout', []],
            ['on_timeout', new \stdClass()],

            ['cache_ttl', null],
            ['cache_ttl', 'string'],
            ['cache_ttl', []],
            ['cache_ttl', new \stdClass()],

            ['recurrence_level', null],
            ['recurrence_level', 'string'],
            ['recurrence_level', []],
            ['recurrence_level', new \stdClass()],

            ['fulfilled', null],
            ['fulfilled', 1],
            ['fulfilled', 'string'],
            ['fulfilled', []],
            ['fulfilled', new \stdClass()],

            ['rejected', null],
            ['rejected', 1],
            ['rejected', 'string'],
            ['rejected', []],
            ['rejected', new \stdClass()],
        ];
    }

    public function invalidDataValueProvider()
    {
        return [
            ['concurrency', 0],
            ['concurrency', -1],
            ['concurrency', -1000],

            ['timeout', 0],
            ['timeout', -1],
            ['timeout', -1000],

            ['cache_ttl', 0],
            ['cache_ttl', -1],
            ['cache_ttl', -1000],

            ['recurrence_level', 0],
            ['recurrence_level', -1],
            ['recurrence_level', -1000],

            ['on_reject', null],
            ['on_reject', []],
            ['on_reject', 1],
            ['on_reject', ''],
            ['on_reject', 'string'],

            ['on_timeout', ''],
            ['on_timeout', 'string'],
        ];
    }

    public function validDataProvider()
    {
        return [
            ['concurrency', 1],
            ['concurrency', 2],
            ['concurrency', 100],

            ['timeout', 1],
            ['timeout', 2],
            ['timeout', 100],

            ['cache_ttl', 1],
            ['cache_ttl', 2],
            ['cache_ttl', 100],

            ['recurrence_level', 1],
            ['recurrence_level', 2],
            ['recurrence_level', 100],

            ['request_options', []],

            ['cache_prefix', ''],
            ['cache_prefix', 'string'],

            ['cache_pool', null],
            ['cache_pool', new Pool(new Ephemeral())],

            ['logger', null],
            ['logger', new VoidLogger()],

            ['base_uri', ''],
            ['base_uri', 'string'],

            ['on_reject', EsiTentacles::ON_REJECT_EXCEPTION],
            ['on_reject', EsiTentacles::ON_REJECT_EMPTY],
            ['on_reject', function(){}],

            ['on_timeout', EsiTentacles::ON_TIMEOUT_H_INCLUDE],
            ['on_timeout', EsiTentacles::ON_TIMEOUT_EXCEPTION],

            ['fulfilled', function(){}],
            ['rejected', function(){}],
        ];
    }
}
