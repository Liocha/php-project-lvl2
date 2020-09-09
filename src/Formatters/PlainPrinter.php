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
        $tmp = nodeRender($node, $parentChain);
        return "{$tmp}";
    }, $diffTree);

    $resault = array_diff($resault, array(''));

    return implode("\n", $resault);
}



function nodeRender($node, $parentChain)
{
    $nodeTemplates = [
        [
            'type' => 'removed',
            'process' => function ($node, $parentChain) {
                $parentChain = strlen($parentChain) === 0 ?  "'{$node['key']}'" : "'{$parentChain}.{$node['key']}'";
                return  "Property {$parentChain} was removed";
            }
        ],
        [
            'type' => 'added',
            'process' => function ($node, $parentChain) {
                $value =  is_array($node['valueAfter']) ? "[complex value]" :
                    "'" . fixBolVal($node['valueAfter']) . "'";
                $parentChain = strlen($parentChain) === 0 ?  "'{$node['key']}'" : "'{$parentChain}.{$node['key']}'";
                return  "Property {$parentChain} was added with value: {$value}";
            }
        ],
        [
            'type' => 'nested',
            'process' => function ($node, $parentChain) {
                $parent_chain = strlen($parentChain) === 0 ?  "{$node['key']}" : "{$parentChain}.{$node['key']}";
                return plainRender($node['children'], $parent_chain);
            }
        ],
        [
            'type' => 'changed',
            'process' => function ($node, $parentChain) {
                $parentChain = strlen($parentChain) === 0 ?  "'{$node['key']}'" :
                    "'{$parentChain}.{$node['key']}'";
                $valueBefore = is_array($node['valueBefore']) ? "[complex value]" :
                    "'" . fixBolVal($node['valueBefore']) . "'";
                $valueAfter = is_array($node['valueAfter']) ?  "[complex value]" :
                    "'" .  fixBolVal($node['valueAfter']) . "'";
                return  "Property {$parentChain} was updated. From {$valueBefore} to {$valueAfter}";
            }
        ],
    ];

    foreach ($nodeTemplates as $nodeTemplate) {
        ['type' => $type, 'process' => $process] = $nodeTemplate;
        if ($type === $node['type']) {
            return $process($node, $parentChain);
        }
    }
}

function fixBolVal($val)
{
    if (gettype($val) === 'boolean') {
        $val = $val ? 'true' : 'false';
    }
    return $val;
}
