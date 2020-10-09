<?php

namespace Differ\Formatters;

function render($diffTree, $format)
{
    switch (mb_strtolower($format)) {
        case 'pretty':
            return Pretty\render($diffTree);
        case 'plain':
            return Plain\render($diffTree);
        case 'json':
            return Json\render($diffTree);
        default:
            throw new \Exception("Unknown output format, current value is {$format}");
    }
}
