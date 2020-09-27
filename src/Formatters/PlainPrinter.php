<?php

namespace Differ\Formatters\PlainPrinter;

use function Funct\Collection\without;

function plainPrinter($diffTree)
{
    $resault =  renderPlain($diffTree);
    return "{$resault}";
}

function renderPlain($diffTree, $parentChain = '')
{

    $resault = array_map(function ($node) use ($parentChain) {
        $type = $node->type;
        $currentParentChain = getCurrentParentChain($parentChain, $node->key);
        switch ($type) {
            case ('removed'):
                return  "Property {$currentParentChain} was removed";
            case ('added'):
                $value =  stringify($node->valueAfter);
                return  "Property {$currentParentChain} was added with value: {$value}";
            case ('nested'):
                $currentParentChain = strlen($parentChain) === 0 ?  "{$node->key}" : "{$parentChain}.{$node->key}";
                return renderPlain($node->children, $currentParentChain);
            case ('changed'):
                $valueBefore = stringify($node->valueBefore);
                $valueAfter = stringify($node->valueAfter);
                return  "Property {$currentParentChain} was updated. From {$valueBefore} to {$valueAfter}";
            default:
                return "";
        }
    }, $diffTree);

    $resault = without($resault, '');

    return implode("\n", $resault);
}

function getCurrentParentChain($parentChain, $nodeKey)
{
    return strlen($parentChain) === 0 ?  "'{$nodeKey}'" : "'{$parentChain}.{$nodeKey}'";
}

function stringify($value)
{
    if (is_object($value)) {
        return  "[complex value]";
    }

    if (is_bool($value)) {
        $value = $value ? 'true' : 'false';
    }

    return "'" . (string) $value . "'";
}
