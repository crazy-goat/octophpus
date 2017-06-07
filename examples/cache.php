<?php
include_once '../vendor/autoload.php';

$text = '<esi:include src="http://crazy-goat.com/octophpus/test/sleep5.php"/>';

$esiInclude = new \CrazyGoat\Octophpus\EsiTentacles([
    'cachePool' => new \Stash\Pool(
        new \Stash\Driver\Ephemeral()
    ),
    'timeout' => 10.0
]);

$start = time();
$esiInclude->decorate($text);
echo 'first request took '.(time() - $start).' seconds'.PHP_EOL;

$start = time();
$esiInclude->decorate($text);
echo 'second request took '.(time() - $start).' seconds'.PHP_EOL;