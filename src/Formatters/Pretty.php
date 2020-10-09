<?php

namespace Differ\Formatters\Pretty;

function render($diffTree)
{
    $result =  renderPretty($diffTree);
    return "{\n{$result}\n}";
}

function renderPretty($diffTree, $depth = 0)
{
    $result = array_map(function ($node) use ($depth) {
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

    return implode("\n", $result);
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

    if (is_object($nodeValue)) {
        $nodeNames = array_keys(get_object_vars($nodeValue));
        $nodeValues = array_map(fn ($name) => stringify($name, $nodeValue->$name, $depth + 1), $nodeNames);
        return "{$ident}{$sign}{$nodeName}: {\n" . implode("\n", $nodeValues) . "\n{$ident}    }";
    }


    return "{$ident}{$sign}{$nodeName}: " . fixBoolVal($nodeValue);
}

function getIdent($depth)
{   
    return str_repeat('    ', $depth);
}

function fixBoolVal($val)
{
    if (!is_bool($val)) {
        return $val;
    }
    return $val ? 'true' : 'false';
}
