<?php

namespace Differ\Formatters\PrettyPrinter;

function pretty_printer($diff_tree)
{
    $resault =  pretty_render($diff_tree);
    return "{\n{$resault}\n}\n";
}

function pretty_render($diff_tree, $deep = 0)
{
    $resault = array_map(function ($node) use ($deep) {
        $tmp = node_render($node, $deep);
        return "{$tmp}";
    }, $diff_tree);

    return implode("\n", $resault);
}

function node_render($node, $deep)
{
    $node_templates = [
        [
            'type' => 'removed',
            'process' => function ($node, $deep) {
                $sign = '  - ';
                $value_before = print_node([$node['key'] => $node['value_before']], $deep, $sign);
                return "{$value_before}";
            }
        ],
        [
            'type' => 'added',
            'process' => function ($node, $deep) {
                $sign = '  + ';
                $ident = get_ident($deep);
                $value_after = print_node([$node['key'] => $node['value_after']], $deep, $sign);
                return  "{$value_after}";
            }
        ],
        [
            'type' => 'nested',
            'process' => function ($node, $deep) {
                $sign = '    ';
                $child = pretty_render($node['children'], $deep + 1);
                $ident = get_ident($deep);
                return "{$ident}{$sign}{$node['key']}: {\n{$child}\n{$ident}    }";
            }
        ],
        [
            'type' => 'unchanged',
            'process' => function ($node, $deep) {
                $sign = '    ';
                $value_before = print_node([$node['key'] => $node['value_before']], $deep, $sign);
                return "{$value_before}";
            }
        ],
        [
            'type' => 'changed',
            'process' => function ($node, $deep) {
                $sign_before = '  - ';
                $sign_after = '  + ';
                $value_before = print_node([$node['key'] => $node['value_before']], $deep, $sign_before);
                $value_after = print_node([$node['key'] => $node['value_after']], $deep, $sign_after);
                return  "{$value_before}\n{$value_after}";
            }
        ],
    ];

    foreach ($node_templates as $node_template) {
        ['type' => $type, 'process' => $process] = $node_template;
        if ($type === $node['type']) {
            return $process($node, $deep);
        }
    }
}

function fix_bool_val($val)
{
    if (gettype($val) === 'boolean') {
        $val = $val ? 'true' : 'false';
    }
    return $val;
}


function get_ident($deep)
{
    $base = '    ';
    $resault = '';
    while ($deep  > 0) {
        $resault = $resault . $base;
        $deep -= 1;
    }
    return $resault;
}

function print_node($items, $deep, $sign = '    ')
{
    $ident = get_ident($deep);
    foreach ($items as $key => $val) {
        if (is_array($val) && count($val) === 1 && array_key_exists('children', $val)) {
            $resault[] = "{$ident}{$sign}{$key}: {\n" . print_node($val['children'], $deep + 1) . "\n{$ident}    }";
        } else {
            $resault[] = "{$ident}{$sign}{$key}: " . fix_bool_val($val);
        }
    }

    return implode("\n", $resault);
}
