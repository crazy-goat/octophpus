<?php
include_once '../vendor/autoload.php';

$text = '<esi:include src="http://crazy-goat.com/octophpus/test"/>';

$esiInclude = new \CrazyGoat\Octophpus\EsiTentacles();
echo $esiInclude->decorate($text);
