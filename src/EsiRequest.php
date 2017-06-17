<?php

namespace CrazyGoat\Octophpus;

use CrazyGoat\Octophpus\Exception\EsiTagParseException;
use CrazyGoat\Octophpus\Validator\EsiAttributeValidator;
use Nunzion\Expect;

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

    /**
     * @var bool
     */
    private $noCache = false;

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
            throw new EsiTagParseException('Unable to parse xml: '.$esiTag);
        }

        $parsedTag = $this->getTag($values);

        $validator = new EsiAttributeValidator($parsedTag['attributes']);
        $validator->validate();

        $this->src = $parsedTag['attributes']['SRC'];
        $this->timeout = isset($parsedTag['attributes']['TIMEOUT']) ? (float)$parsedTag['attributes']['TIMEOUT'] : null;
        $this->noCache = isset($parsedTag['attributes']['NOCACHE']);

    }

    private function getTag(array $tags) : array
    {
        Expect::that($tags)->itsArrayElement(0);
        $tag = $tags[0];

        Expect::that($tag)->isArray();
        Expect::that($tag)->itsArrayElement('tag')->isString()->equals('ESI:INCLUDE');
        Expect::that($tag)->itsArrayElement('attributes')->isArray();
        Expect::that($tag['attributes'])->itsArrayElement('SRC')->isString();

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

        if (!is_null($this->timeout)) {
            $options['connect_timeout'] = $this->timeout;
        }

        return $options;
    }

    /**
     * @return bool
     */
    public function noCache(): bool
    {
        return $this->noCache;
    }
}