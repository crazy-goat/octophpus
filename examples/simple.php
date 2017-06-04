<?php
include_once "../vendor/autoload.php";

$text = '<esi:include src="http://crazy-goat.com/octophpus/test"/>';

$mantle = new \CrazyGoat\Octophpus\Mantle();
echo $mantle->decorate($text);
