<?php
include_once "../vendor/autoload.php";

$text = '<esi:include src="http://crazy-goat.com/octophpus/test/error_404"/>';

$reject_closure = function (string &$data, array $esiRequests) {
    return (function (\Exception $reason, int $index) use (&$data, $esiRequests) {
        var_dump($reason->getCode());
    });
};

$mantle = new \CrazyGoat\Octophpus\Mantle(
    ['on_reject' => $reject_closure ]
);
echo $mantle->decorate($text);
