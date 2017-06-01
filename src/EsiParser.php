<?php

namespace CrazyGoat\Octophpus;

use CrazyGoat\Octophpus\Exception\EsiTagParseException;

class EsiParser
{
    /**
     * @var string
     */
    private $src;

    public function __construct(string $esiTag)
    {
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

        $parsedTag = $this->checkTag($values);

        $this->src = $parsedTag['attributes']['SRC'];
    }

    private function checkTag(array $tags) : array
    {
        if (!is_array($tags) && !isset($tagw[0])) {
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
}