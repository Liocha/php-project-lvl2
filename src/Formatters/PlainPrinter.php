<?php

namespace Differ\Formatters\PlainPrinter;

use function Differ\Helpers\fixBoolVal;

function plainPrinter($diffTree)
{
    $resault =  plainRender($diffTree);
    return "{$resault}\n";
}

function plainRender($diffTree, $parentChain = '')
{

    $resault = array_map(function ($node) use ($parentChain) {
        $type = $node->type;
        switch ($type) {
            case ('removed'):
                $parentChain = strlen($parentChain) === 0 ?  "'{$node->key}'" : "'{$parentChain}.{$node->key}'";
                return  "Property {$parentChain} was removed";
            case ('added'):
                $value =  is_object($node->valueAfter) ? "[complex value]" :
                    "'" . fixBoolVal($node->valueAfter) . "'";
                $parentChain = strlen($parentChain) === 0 ?  "'{$node->key}'" : "'{$parentChain}.{$node->key}'";
                return  "Property {$parentChain} was added with value: {$value}";
            case ('nested'):
                $parent_chain = strlen($parentChain) === 0 ?  "{$node->key}" : "{$parentChain}.{$node->key}";
                return plainRender($node->children, $parent_chain);
            case ('changed'):
                $parentChain = strlen($parentChain) === 0 ?  "'{$node['key']}'" :
                    "'{$parentChain}.{$node->key}'";
                $valueBefore = is_object($node->valueBefore) ? "[complex value]" :
                    "'" . fixBoolVal($node->valueBefore) . "'";
                $valueAfter = is_object($node->valueAfter) ?  "[complex value]" :
                    "'" .  fixBoolVal($node->valueAfter) . "'";
                return  "Property {$parentChain} was updated. From {$valueBefore} to {$valueAfter}";
        }
    }, $diffTree);

    $resault = array_diff($resault, array(''));

    return implode("\n", $resault);
}
