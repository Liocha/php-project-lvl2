<?php

namespace Differ\Parsers;

use Symfony\Component\Yaml\Yaml;

function parse($file_data)
{
    [$data, $type] = $file_data;
    switch ($type) {
        case 'json':
            return json_decode($data);
        case 'yml' || 'yaml':
            return Yaml::parse($data, Yaml::PARSE_OBJECT_FOR_MAP);
        default:
            throw new \Exception("type '{$type}' not supported");
    }
}
