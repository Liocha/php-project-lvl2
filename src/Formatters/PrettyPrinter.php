<?php

namespace Differ\Formatters\PrettyPrinter;

function pretty_render($diff_tree, $deep = 0)
{
    $resault = array_map(function ($node) use ($deep) {
        $tmp = get_template($node, $deep);
        return "{$tmp}";
    }, $diff_tree);

    return implode("\n", $resault);
}

function get_template($node, $deep)
{
    $node_templates = [
        [
            'type' => 'removed',
            'process' => function ($node, $deep) {
                $value_before = is_array($node['value_before']) ? print_array([$node['key'] => $node['value_before']], $deep + 1) : "{$node['key']}: " .  fix_bool_val($node['value_before']);
                $ident = get_ident($deep);
                return  "{$ident}  - {$value_before}";
            }
        ],
        [
            'type' => 'added',
            'process' => function ($node, $deep) {
                $value_after = is_array($node['value_after']) ? print_array([$node['key'] => $node['value_after']], $deep + 1) : "{$node['key']}: " .  fix_bool_val($node['value_after']);
                $ident = get_ident($deep);
                return  "{$ident}  + {$value_after}";
            }
        ],
        [
            'type' => 'nested',
            'sign' => '  ',
            'process' => function ($node, $deep) {
                $child = pretty_render($node['children'], $deep + 1);
                $ident = get_ident($deep);
                return "{$ident}    {$node['key']}: {\n{$child}\n{$ident}    }";
            }
        ],
        [
            'type' => 'unchanged',
            'process' => fn ($node, $deep) => get_ident($deep) . "    {$node['key']}: {$node['value_before']}"
        ],
        [
            'type' => 'changed',
            'process' => function ($node, $deep) {
                $value_before = is_array($node['value_before']) ? print_array([$node['key'] => $node['value_before']], $deep) : "{$node['key']}: " .  fix_bool_val($node['value_before']);
                $value_after = is_array($node['value_after']) ? print_array([$node['key'] => $node['value_after']], $deep) :  "{$node['key']}: " . fix_bool_val($node['value_after']);
                return     get_ident($deep) . "  - {$value_before}\n" . get_ident($deep) . "  + {$value_after}";
            }
        ]

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

function print_array($items, $deep, $space = '')
{
    foreach ($items as $key => $val) {
        if (is_array($val) && count($val) === 1 && array_key_exists('children', $val)) {
            $resault[] = $space . "{$key}: {\n" . print_array($val['children'], $deep + 1, get_ident($deep + 1)) . "\n" . get_ident($deep) . "}";
        } else {
            $resault[] = get_ident($deep) . "    {$key}: {$val}";
        }
    }

    return implode("\n", $resault);
}
