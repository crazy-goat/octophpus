<?php

namespace CrazyGoat\Octophpus;

class EsiParser
{
    /**
     * @var string
     */
    private $data;

    private $matches = null;

    public function __construct(?string $data = '')
    {
        $this->data = $data;
    }

    public function parse(?string $data = null) : int
    {
        if (!empty($data)) {
            $this->data = $data;
        }

        $found = preg_match_all('/<esi:include [^>]*\\/>/ims',  $this->data, $this->matches);

        return $found;
    }

    public  function esiRequests() : array
    {
        if (is_null($this->matches)) {
            $this->parse();
        }

        $ret = [];
        foreach ($this->matches[0] as $esiTag) {
            $ret[] = new EsiRequest($esiTag);
        }
        return $ret;
    }
}