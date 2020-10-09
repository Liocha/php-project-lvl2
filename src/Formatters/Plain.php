<?php

namespace Differ\Formatters\Plain;

use function Funct\Collection\without;

function render($diffTree)
{
    $result = renderPlain($diffTree);
    return (string) $result;
}

function renderPlain($diffTree, $ancestry = '')
{

    $result = array_map(function ($node) use ($ancestry) {
        $type = $node['type'];
        $currentAncestry = getCurrentAncestry($ancestry, $node['key']);
        switch ($type) {
            case ('removed'):
                return  "Property {$currentAncestry} was removed";
            case ('added'):
                $value =  stringify($node['valueAfter']);
                return  "Property {$currentAncestry} was added with value: {$value}";
            case ('nested'):
                return renderPlain(
                    $node['children'],
                    strlen($ancestry) === 0 ?  "{$node['key']}" : "{$ancestry}.{$node['key']}"
                );
            case ('changed'):
                $valueBefore = stringify($node['valueBefore']);
                $valueAfter = stringify($node['valueAfter']);
                return  "Property {$currentAncestry} was updated. From {$valueBefore} to {$valueAfter}";
            case ('unchanged'):
                return "";
            default:
                throw new Error("Unknown format {$type}");
        }
    }, $diffTree);

    return implode("\n", without($result, ''));
}

function getCurrentAncestry($ancestry, $nodeKey)
{
    return strlen($ancestry) === 0 ?  "'{$nodeKey}'" : "'{$ancestry}.{$nodeKey}'";
}

function stringify($value)
{
    if (is_array($value) || is_object($value)) {
        return  "[complex value]";
    }

    if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
    }

    return "'" . (string) $value . "'";
}
