<?php

namespace CrazyGoat\Octophpus\Test;

use CrazyGoat\Octophpus\EsiParser;
use PHPUnit\Framework\TestCase;

class EsiParserTest extends TestCase
{
    public function testEsiInclude()
    {
        $esiParser = new EsiParser();
        self::assertEquals($esiParser->parse('test<esi:include />dasdas'), 1);
        self::assertEquals($esiParser->parse('test<eSi:Include />dasdas'), 1);
    }
}
