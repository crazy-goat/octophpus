<?php
include_once '../vendor/autoload.php';

$text = '<esi:include src="http://crazy-goat.com/octophpus/test/sleep5.php"/>';

$esiInclude = new \CrazyGoat\Octophpus\EsiTentacles([
    'timeout' => 1,
    'on_timeout' => \CrazyGoat\Octophpus\EsiTentacles::ON_TIMEOUT_H_INCLUDE
]);

$start = time();
echo $esiInclude->decorate($text);
