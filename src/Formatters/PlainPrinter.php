<?php

namespace Differ\Formatters\PlainPrinter;

function plain_printer($diff_tree)
{
    $resault =  plain_render($diff_tree);
    return "{$resault}\n";
}

function plain_render($diff_tree, $parent_chain = '')
{
    $resault = array_map(function ($node) use ($parent_chain) {
        $tmp = node_render($node, $parent_chain);
        return "{$tmp}";
    }, $diff_tree);

    $resault = array_diff($resault, array(''));

    return implode("\n", $resault);
}



function node_render($node, $parent_chain)
{
    $node_templates = [
        [
            'type' => 'removed',
            'process' => function ($node, $parent_chain) {
                $parent_chain = strlen($parent_chain) === 0 ?  "'{$node['key']}'" : "'{$parent_chain}.{$node['key']}'";
                return  "Property {$parent_chain} was removed";
            }
        ],
        [
            'type' => 'added',
            'process' => function ($node, $parent_chain) {
                $value =  is_array($node['value_after']) ? "[complex value]" :
                                                           "'" . fix_bol_val($node['value_after']) . "'";
                $parent_chain = strlen($parent_chain) === 0 ?  "'{$node['key']}'" : "'{$parent_chain}.{$node['key']}'";
                return  "Property {$parent_chain} was added with value: {$value}";
            }
        ],
        [
            'type' => 'nested',
            'process' => function ($node, $parent_chain) {
                $parent_chain = strlen($parent_chain) === 0 ?  "{$node['key']}" : "{$parent_chain}.{$node['key']}";
                return plain_render($node['children'], $parent_chain);
            }
        ],
        [
            'type' => 'changed',
            'process' => function ($node, $parent_chain) {
                $parent_chain = strlen($parent_chain) === 0 ?  "'{$node['key']}'" :
                                                               "'{$parent_chain}.{$node['key']}'";
                $value_before = is_array($node['value_before']) ? "[complex value]" :
                                                                  "'" . fix_bol_val($node['value_before']) . "'";
                $value_after = is_array($node['value_after']) ?  "[complex value]" :
                                                                 "'" .  fix_bol_val($node['value_after']) . "'";
                return  "Property {$parent_chain} was updated. From {$value_before} to {$value_after}";
            }
        ],
    ];

    foreach ($node_templates as $node_template) {
        ['type' => $type, 'process' => $process] = $node_template;
        if ($type === $node['type']) {
            return $process($node, $parent_chain);
        }
    }
}

function fix_bol_val($val)
{
    if (gettype($val) === 'boolean') {
        $val = $val ? 'true' : 'false';
    }
    return $val;
}
