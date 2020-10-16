<?php

namespace Differ\Formatters\Pretty;

const BASE_IDENT = "  ";

function render($diffTree)
{
    $result = renderPretty($diffTree);
    return "{\n{$result}\n}";
}


function renderPretty($diffTree, $depth = 1)
{
    $result = array_map(function ($node) use ($depth) {
        $type = $node['type'];
        $ident = getIdent($depth, true);
        $nodeName = $node['key'];
        switch ($type) {
            case ($type === 'removed'):
                $sign = '- ';
                $value  = stringify($node['valueBefore'], $depth + 1);
                return "{$ident}{$sign}{$nodeName}: {$value}";
            case ($type === 'added'):
                $sign = '+ ';
                $value  = stringify($node['valueAfter'], $depth + 1);
                return "{$ident}{$sign}{$nodeName}: {$value}";
            case ($type === 'nested'):
                $sign = '  ';
                $child = renderPretty($node['children'], $depth + 1);
                return "{$ident}{$sign}{$nodeName}: {\n{$child}\n{$ident}  }";
            case ('changed'):
                $signBefore = '- ';
                $signAfter = '+ ';
                $valueBefore = stringify($node['valueBefore'], $depth + 1);
                $valueAfter = stringify($node['valueAfter'], $depth + 1);
                return "{$ident}{$signBefore}{$nodeName}: {$valueBefore}\n" .
                    "{$ident}{$signAfter}{$nodeName}: {$valueAfter}";
            case ('unchanged'):
                $sign = '  ';
                $value = stringify($node['valueBefore'], $depth + 1);
                return "{$ident}{$sign}{$nodeName}: {$value}";
            default:
                throw new \Exception("Unknown type node, current value is {$type}");
        }
    }, $diffTree);

    return implode("\n", $result);
}


function stringify($nodeValue, $depth)
{
    $ident = getIdent($depth);

    if (is_object($nodeValue)) {
        $nodeNames = array_keys(get_object_vars($nodeValue));
        $nodeValues = array_map(
            fn ($name) => "{$ident}{$name}: " . stringify($nodeValue->$name, $depth + 1),
            $nodeNames
        );
        $value = implode("\n", $nodeValues);
        $tmp = getIdent($depth - 1);
        return "{\n{$value}\n{$tmp}}";
    }

    if (is_array($nodeValue)) {
        $items = array_map(fn ($item) => stringify($item, $depth), $nodeValue);
        $result = implode(", ", $items);
        return "[{$result}]";
    }

    if (is_bool($nodeValue)) {
        return $nodeValue ? 'true' : 'false';
    }

    return "{$nodeValue}";
}

function getIdent($depth, $hasSign = false)
{
    if ($hasSign) {
        $indentSize = $depth * 2 - 1;
    } else {
        $indentSize = $depth * 2;
    }

    return  str_repeat(BASE_IDENT, $indentSize);
}
