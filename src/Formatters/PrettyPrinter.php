<?php

namespace Differ\Formatters\PrettyPrinter;

function prettyPrinter($diffTree)
{
    $resault =  prettyRender($diffTree);
    return "{\n{$resault}\n}\n";
}

function prettyRender($diffTree, $deep = 0)
{
    $resault = array_map(function ($node) use ($deep) {
        $tmp = nodeRender($node, $deep);
        return "{$tmp}";
    }, $diffTree);

    return implode("\n", $resault);
}
function nodeRender($node, $deep)
{
    $node_templates = [
        [
            'type' => 'removed',
            'process' => function ($node, $deep) {
                $sign = '  - ';
                $valueBefore = printNode([$node->key => $node->valueBefore], $deep, $sign);
                return "{$valueBefore}";
            }
        ],
        [
            'type' => 'added',
            'process' => function ($node, $deep) {
                $sign = '  + ';
                $valueAfter = printNode([$node->key => $node->valueAfter], $deep, $sign);
                return  "{$valueAfter}";
            }
        ],
        [
            'type' => 'nested',
            'process' => function ($node, $deep) {
                $sign = '    ';
                $child = prettyRender($node->children, $deep + 1);
                $ident = getIdent($deep);
                return "{$ident}{$sign}{$node->key}: {\n{$child}\n{$ident}    }";
            }
        ],
        [
            'type' => 'unchanged',
            'process' => function ($node, $deep) {
                $sign = '    ';
                $valueBefore = printNode([$node->key => $node->valueBefore], $deep, $sign);
                return "{$valueBefore}";
            }
        ],
        [
            'type' => 'changed',
            'process' => function ($node, $deep) {
                $signBefore = '  - ';
                $signAfter = '  + ';
                $valueBefore = printNode([$node->key => $node->valueBefore], $deep, $signBefore);
                $valueAfter = printNode([$node->key => $node->valueAfter], $deep, $signAfter);
                return  "{$valueBefore}\n{$valueAfter}";
            }
        ],
    ];

    foreach ($node_templates as $node_template) {
        ['type' => $type, 'process' => $process] = $node_template;
        if ($type === $node->type) {
            return $process($node, $deep);
        }
    }
}

function printNode($items, $deep, $sign = '    ')
{
    $ident = getIdent($deep);
    foreach ($items as $key => $val) {
        if (is_object($val)) {
            $resault[] = "{$ident}{$sign}{$key}: {\n" . printNode($val, $deep + 1) . "\n{$ident}    }";
        } else {
            $resault[] = "{$ident}{$sign}{$key}: " . fixBoolVal($val);
        }
    }

    return implode("\n", $resault);
}

function fixBoolVal($val)
{
    if (gettype($val) === 'boolean') {
        $val = $val ? 'true' : 'false';
    }
    return $val;
}


function getIdent($deep)
{
    $base = '    ';
    $resault = '';
    while ($deep  > 0) {
        $resault = $resault . $base;
        $deep -= 1;
    }
    return $resault;
}
