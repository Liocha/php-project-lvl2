<?php

namespace Differ\Formatters\PrettyPrinter;

use function Differ\Helpers\fixBoolVal;

function prettyPrinter($diffTree)
{
    $resault =  prettyRender($diffTree);
    return "{\n{$resault}\n}\n";
}

function prettyRender($diffTree, $deep = 0)
{
    $resault = array_map(function ($node) use ($deep) {
        $type = $node->type;
        if ($type === 'removed') {
            $sign = '  - ';
            $valueBefore = printNode([$node->key => $node->valueBefore], $deep, $sign);
            return "{$valueBefore}";
        }
        if ($type === 'added') {
            $sign = '  + ';
            $valueAfter = printNode([$node->key => $node->valueAfter], $deep, $sign);
            return  "{$valueAfter}";
        }
        if ($type === 'nested') {
            $sign = '    ';
            $child = prettyRender($node->children, $deep + 1);
            $ident = getIdent($deep);
            return "{$ident}{$sign}{$node->key}: {\n{$child}\n{$ident}    }";
        }
        if ($type === 'changed') {
            $signBefore = '  - ';
            $signAfter = '  + ';
            $valueBefore = printNode([$node->key => $node->valueBefore], $deep, $signBefore);
            $valueAfter = printNode([$node->key => $node->valueAfter], $deep, $signAfter);
            return  "{$valueBefore}\n{$valueAfter}";
        }
        $sign = '    ';
        $valueBefore = printNode([$node->key => $node->valueBefore], $deep, $sign);
        return "{$valueBefore}";
    }, $diffTree);

    return implode("\n", $resault);
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
