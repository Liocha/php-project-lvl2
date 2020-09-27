<?php

namespace Differ\Formatters\PrettyPrinter;

function prettyPrinter($diffTree)
{
    $resault =  renderPretty($diffTree);
    return "{\n{$resault}\n}";
}

function renderPretty($diffTree, $depth = 0)
{
    $resault = array_map(function ($node) use ($depth) {
        $type = $node->type;
        switch ($type) {
            case ($type === 'removed'):
                $sign = '  - ';
                return stringify($node->key, $node->valueBefore, $depth, $sign);
            case ($type === 'added'):
                $sign = '  + ';
                return stringify($node->key, $node->valueAfter, $depth, $sign);
            case ($type === 'nested'):
                $sign = '    ';
                $child = renderPretty($node->children, $depth + 1);
                $ident = getIdent($depth);
                return "{$ident}{$sign}{$node->key}: {\n{$child}\n{$ident}    }";
            case ('changed'):
                $signBefore = '  - ';
                $signAfter = '  + ';
                $valueBefore = stringify($node->key, $node->valueBefore, $depth, $signBefore);
                $valueAfter = stringify($node->key, $node->valueAfter, $depth, $signAfter);
                return  "{$valueBefore}\n{$valueAfter}";
            default:
                $sign = '    ';
                return stringify($node->key, $node->valueBefore, $depth, $sign);
        }
    }, $diffTree);

    return implode("\n", $resault);
}


function stringify($nodeName, $nodeValue, $depth, $sign = '    ')
{
    $ident = getIdent($depth);

    if (is_object($nodeValue)) {
        $nodeNames = array_keys(get_object_vars($nodeValue));
        $nodeValues = array_map(fn ($name) => stringify($name, $nodeValue->$name, $depth + 1), $nodeNames);
        return "{$ident}{$sign}{$nodeName}: {\n" . implode("\n", $nodeValues) . "\n{$ident}    }";
    }

    if (is_array($nodeValue)) {
        $items = array_map(fn ($item) => fixBoolVal($item), $nodeValue);
        return "{$ident}{$sign}{$nodeName}: [" . implode(", ", $items) . "]";
    }

    return "{$ident}{$sign}{$nodeName}: " . fixBoolVal($nodeValue);
}

function getIdent($depth)
{
    $tmp = $depth;
    $base = '    ';
    $resault = '';
    while ($tmp  > 0) {
        $resault = $resault . $base;
        $tmp -= 1;
    }
    return $resault;
}

function fixBoolVal($val)
{
    if (!is_bool($val)) {
        return $val;
    }
    return $val ? 'true' : 'false';
}
