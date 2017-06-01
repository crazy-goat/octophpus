<?php

namespace CrazyGoat\Octophpus;

use CrazyGoat\Octophpus\Exception\EsiTagParseException;
use CrazyGoat\Octophpus\Validator\EsiAttributeValidator;

class EsiRequest
{
    /**
     * @var string
     */
    private $src;

    /**
     * @var string
     */
    private $esiTag;

    /**
     * @var float|null
     */
    private $timeout = null;


    public function __construct(string $esiTag)
    {
        $this->esiTag = $esiTag;
        $this->parse($esiTag);
    }

    private function parse(string $esiTag) : void
    {
        $p = xml_parser_create();
        $parseStatus = xml_parse_into_struct($p, $esiTag, $values);
        xml_parser_free($p);

        if ($parseStatus == 0) {
            throw new EsiTagParseException("Unable to parse xml: ".$esiTag);
        }

        $parsedTag = $this->getTag($values);

        $validator = new EsiAttributeValidator($parsedTag['attributes']);
        $validator->validate();

        $this->src = $parsedTag['attributes']['SRC'];
        $this->timeout = isset($parsedTag['attributes']['TIMEOUT']) ? (float)$parsedTag['attributes']['TIMEOUT'] : null;
    }

    private function getTag(array $tags) : array
    {
        if (!is_array($tags) || !isset($tags[0])) {
            throw new EsiTagParseException("No valid html tags found");
        }

        $tag = $tags[0];

        if ($tag['tag'] !== 'ESI:INCLUDE') {
            throw new EsiTagParseException("No valid html tags found");
        }

        if (!isset($tag['attributes']['SRC'])) {
            throw new EsiTagParseException("Esi tag does not have required parameter src");
        }

        return $tag;
    }

    /**
     * @return string
     */
    public function getSrc(): string
    {
        return $this->src;
    }

    /**
     * @return string
     */
    public function getEsiTag(): string
    {
        return $this->esiTag;
    }

    public function requestOptions() : array
    {
        $options = [];

        if (!empty($this->timeout)) {
            $options['connect_timeout'] = $this->timeout;
        }

        return $options;
    }
}