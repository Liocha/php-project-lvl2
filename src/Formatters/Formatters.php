<?php

namespace Differ\Formatters\Formatters;

use function Differ\Formatters\PrettyPrinter\pretty_printer;
use function Differ\Formatters\PlainPrinter\plain_printer;
use function Differ\Formatters\JsonPrinter\json_printer;

function render_by_format($diff_tree, $format)
{
    switch (mb_strtolower($format)) {
        case 'pretty':
            return pretty_printer($diff_tree);
        case 'plain':
            return plain_printer($diff_tree);
        case 'json':
            return json_printer($diff_tree);
        default:
            throw new \Exception("Unknown output format, current value is {$format}");
    }
}
