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
        $type = $node['type'];
        switch ($type) {
            case ($type === 'removed'):
                $sign = '  - ';
                return stringify($node['key'], $node['valueBefore'], $depth, $sign);
            case ($type === 'added'):
                $sign = '  + ';
                return stringify($node['key'], $node['valueAfter'], $depth, $sign);
            case ($type === 'nested'):
                $sign = '    ';
                $child = renderPretty($node['children'], $depth + 1);
                $ident = getIdent($depth);
                return "{$ident}{$sign}{$node['key']}: {\n{$child}\n{$ident}    }";
            case ('changed'):
                $signBefore = '  - ';
                $signAfter = '  + ';
                $valueBefore = stringify($node['key'], $node['valueBefore'], $depth, $signBefore);
                $valueAfter = stringify($node['key'], $node['valueAfter'], $depth, $signAfter);
                return  "{$valueBefore}\n{$valueAfter}";
            default:
                $sign = '    ';
                return stringify($node['key'], $node['valueBefore'], $depth, $sign);
        }
    }, $diffTree);

    return implode("\n", $resault);
}


function stringify($nodeName, $nodeValue, $depth, $sign = '    ')
{
    $ident = getIdent($depth);

    if (is_array($nodeValue) && array_key_exists('children', $nodeValue)) {
        $nodeChildren = array_map(function ($node) use ($depth) {
            ['key' => $name, 'value' => $valie] = $node;
            return  stringify($name, $valie, $depth + 1);
        }, $nodeValue['children']);

        return "{$ident}{$sign}{$nodeName}: {\n" . implode("\n", $nodeChildren) . "\n{$ident}    }";
    }

    if (is_array($nodeValue)) {
        $items = array_map(fn ($item) => fixBoolVal($item), $nodeValue);
        return "{$ident}{$sign}{$nodeName}: [" . implode(", ", $items) . "]";
    }

    return "{$ident}{$sign}{$nodeName}: " . fixBoolVal($nodeValue);
}

function getIdent($depth)
{
    $base = '    ';
    $resault = '';
    while ($depth  > 0) {
        $resault = $resault . $base;
        $depth -= 1;
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
