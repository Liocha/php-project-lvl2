<?php

namespace Differ\Formatters\PrettyPrinter;

function pretty_render($diff_tree)
{

    $resault = array_map(function ($node) {
        return get_template($node);
    }, $diff_tree);

    $resault = array_merge(["\n{"], $resault, ["}\n"]);

    return implode("\n", $resault);
}

function get_template($node)
{
    $node_templates = [
        [
            'type' => 'removed',
            'process' => fn ($node) => "  - {$node['key']}: {$node['value_before']}"
        ],
        [
            'type' => 'added',
            'process' => fn ($node) => "  + {$node['key']}: " . fix_bool_val($node['value_after'])
        ],
        [
            'type' => 'nested',
            'process' => fn ($node) => null
        ],
        [
            'type' => 'unchanged',
            'process' => fn ($node) => "    {$node['key']}: {$node['value_before']}"
        ],
        [
            'type' => 'changed',
            'process' => fn ($node) => "  - {$node['key']}: {$node['value_before']}\n" .
                "  + {$node['key']}: {$node['value_after']}"
        ]

    ];

    foreach ($node_templates as $node_template) {
        ['type' => $type, 'process' => $process] = $node_template;
        if ($type === $node['type']) {
            return $process($node);
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
