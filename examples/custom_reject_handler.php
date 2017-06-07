<?php
include_once '../vendor/autoload.php';

$text = '<esi:include src="http://crazy-goat.com/octophpus/test/error_404"/>';
/**
 * @param string $data
 * @param \CrazyGoat\Octophpus\EsiRequest[] $esiRequests
 * @return Closure
 */
$reject_closure = function(string&$data, array $esiRequests) {
    return (function(\Exception $reason, int $index) use (&$data, $esiRequests) {
        echo 'Unable to fetch request ('.$esiRequests[$index]->getSrc().') reason : '.$reason->getMessage();
        throw $reason;
    });
};

$esiInclude = new \CrazyGoat\Octophpus\EsiTentacles(
    ['on_reject' => $reject_closure]
);

echo $esiInclude->decorate($text);
