<?php

namespace Differ\Formatters\PlainPrinter;

function plainPrinter($diffTree)
{
    $resault =  plainRender($diffTree);
    return "{$resault}\n";
}

function plainRender($diffTree, $parentChain = '')
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
                return plainRender($node->children, $currentParentChain);
            case ('changed'):
                $valueBefore = stringify($node->valueBefore);
                $valueAfter = stringify($node->valueAfter);
                return  "Property {$currentParentChain} was updated. From {$valueBefore} to {$valueAfter}";
        }
    }, $diffTree);

    $resault = array_diff($resault, array(''));

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

    return "'{$value}'";
}
