<?php

namespace Differ\Parsers;

use Symfony\Component\Yaml\Yaml;

function parse($data, $format)
{
    switch ($format) {
        case 'json':
            return json_decode($data);
        case 'yml':
            return Yaml::parse($data, Yaml::PARSE_OBJECT_FOR_MAP);
        case 'yaml':
            return Yaml::parse($data, Yaml::PARSE_OBJECT_FOR_MAP);
        default:
            throw new \Exception("format '{$format}' not supported");
    }
}
