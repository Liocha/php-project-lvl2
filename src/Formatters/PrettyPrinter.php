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
        $type = $node->type;
        switch ($type) {
            case ($type === 'removed'):
                $sign = '  - ';
                $valueBefore = stringify($node->key, $node->valueBefore, $deep, $sign);
                return "{$valueBefore}";
            case ($type === 'added'):
                $sign = '  + ';
                $valueAfter = stringify($node->key, $node->valueAfter, $deep, $sign);
                return  "{$valueAfter}";
            case ($type === 'nested'):
                $sign = '    ';
                $child = prettyRender($node->children, $deep + 1);
                $ident = getIdent($deep);
                return "{$ident}{$sign}{$node->key}: {\n{$child}\n{$ident}    }";
            case ('changed'):
                $signBefore = '  - ';
                $signAfter = '  + ';
                $valueBefore = stringify($node->key, $node->valueBefore, $deep, $signBefore);
                $valueAfter = stringify($node->key, $node->valueAfter, $deep, $signAfter);
                return  "{$valueBefore}\n{$valueAfter}";
            default:
                $sign = '    ';
                $valueBefore = stringify($node->key, $node->valueBefore, $deep, $sign);
                return "{$valueBefore}";
        }
    }, $diffTree);

    return implode("\n", $resault);
}


function stringify($nodeName, $nodeValue, $deep, $sign = '    ')
{
    $ident = getIdent($deep);

    if (is_object($nodeValue)) {
        $nodeNames = array_keys(get_object_vars($nodeValue));
        $nodeValues = array_map(fn ($name) => stringify($name, $nodeValue->$name, $deep + 1), $nodeNames);
        return "{$ident}{$sign}{$nodeName}: {\n" . implode("\n", $nodeValues) . "\n{$ident}    }";
    }

    if (is_array($nodeValue)) {
        $items = array_map(fn ($item) => fixBoolVal($item), $nodeValue);
        return "{$ident}{$sign}{$nodeName}: [" . implode(", ", $items) . "]";
    }

    return "{$ident}{$sign}{$nodeName}: " . fixBoolVal($nodeValue);
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

function fixBoolVal($val)
{
    if (!is_bool($val)) {
        return $val;
    }
    return $val ? 'true' : 'false';
}
