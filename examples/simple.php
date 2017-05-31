<?php
include_once "../vendor/autoload.php";

$text = '<esi:include src="http://crazy-goat.com/"/>';

$octophpus = new \CrazyGoat\Octophpus\Mantle();
echo $octophpus->decorate($text);
