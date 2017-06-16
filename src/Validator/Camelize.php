<?php


namespace CrazyGoat\Octophpus\Validator;


trait Camelize
{
    private function camlize(string $data): string
    {
        return implode('',
            array_map(
                'ucfirst',
                explode('_', $data)
            )
        );
    }
}