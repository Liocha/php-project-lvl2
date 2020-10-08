<?php

namespace Differ\Formatters;

use function Differ\Formatters\PrettyPrinter\prettyPrinter;
use function Differ\Formatters\PlainPrinter\plainPrinter;
use function Differ\Formatters\JsonPrinter\jsonPrinter;

function render($diffTree, $format)
{
    switch (mb_strtolower($format)) {
        case 'pretty':
            return prettyPrinter($diffTree);
        case 'plain':
            return plainPrinter($diffTree);
        case 'json':
            return jsonPrinter($diffTree);
        default:
            throw new \Exception("Unknown output format, current value is {$format}");
    }
}
