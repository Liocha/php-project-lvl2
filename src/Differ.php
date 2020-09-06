<?php

namespace Differ\Differ;

use function Differ\Formatters\Formatters\render_by_format;
use function Differ\Parsers\parse;

function gen_diff($path_to_first_file, $path_to_second_file, $format)
{
    $data_from_first_file = get_content($path_to_first_file);
    $data_from_second_file = get_content($path_to_second_file);


    $type_first_file = get_type_file($path_to_first_file);
    $type_second_file = get_type_file($path_to_second_file);

    $first_file_obj =  parse($data_from_first_file, $type_first_file);
    $second_file_obj = parse($data_from_second_file, $type_second_file);

    $first_file_assoc = converting_data_to_assoc($first_file_obj);
    $second_file_assoc = converting_data_to_assoc($second_file_obj);

    $dif_tree =  build_diff_tree($first_file_assoc, $second_file_assoc);

    return render_by_format($dif_tree, $format);
}

function build_diff_tree($first_file_assoc, $second_file_assoc)
{
    $all_node_names = array_unique(array_merge(array_keys($first_file_assoc), array_keys($second_file_assoc)));

    return array_map(function ($node_key) use ($first_file_assoc, $second_file_assoc) {
        ['type' => $type, 'process' => $process] = create_node($node_key, $first_file_assoc, $second_file_assoc);
        if ($type === 'nested') {
            $first_children = $first_file_assoc[$node_key]['children'];
            $second_children = $second_file_assoc[$node_key]['children'];
            return [
                'key' => $node_key,
                'type' => $type,
                'children' => build_diff_tree($first_children, $second_children)
            ];
        }
        return $process($node_key, $first_file_assoc, $second_file_assoc, $type);
    }, $all_node_names);
}

function get_value_type($node, $node2)
{
    if (gettype($node) === gettype($node2)) {
        return null;
    } else {
        return null;
    }
}


function create_node($node_key, $first_assoc, $second_assoc)
{
    $node_types = [
        [
            'type' => 'removed',
            'check' => fn ($node_key, $first_assoc, $second_assoc) =>
            array_key_exists($node_key, $first_assoc) && !array_key_exists($node_key, $second_assoc),
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) =>
            ['key' => $node_key, 'type' => $type, 'value_before' => $first_assoc[$node_key]]
        ],
        [
            'type' => 'added',
            'check' => fn ($node_key, $first_assoc, $second_assoc) =>
            !array_key_exists($node_key, $first_assoc) && array_key_exists($node_key, $second_assoc),
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) =>
            ['key' => $node_key, 'type' => $type, 'value_after' => $second_assoc[$node_key]]
        ],
        [
            'type' => 'changed',
            'check' => function ($node_key, $first_assoc, $second_assoc) {
                if (gettype($first_assoc[$node_key]) !==  gettype($second_assoc[$node_key])) {
                    return true;
                }
                return false;
            },
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) =>
            ['key' => $node_key, 'type' =>  $type, 'value_before' =>
            $first_assoc[$node_key], 'value_after' => $second_assoc[$node_key]]
        ],
        [
            'type' => 'nested',
            'check' => function ($node_key, $first_assoc, $second_assoc) {
                if (is_array($first_assoc[$node_key]) && is_array($second_assoc[$node_key])) {
                    return array_key_exists('children', $first_assoc[$node_key]) &&
                        array_key_exists('children', $second_assoc[$node_key]);
                } else {
                    return false;
                }
            },
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) =>
            ['key' => $node_key, 'type' => $type, 'children' =>
            [$first_assoc['children'], $second_assoc['children']]]
        ],
        [
            'type' => 'unchanged',
            'check' => fn ($node_key, $first_assoc, $second_assoc) =>
            $first_assoc[$node_key] === $second_assoc[$node_key],
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) =>
            ['key' => $node_key, 'type' =>  $type, 'value_before' => $first_assoc[$node_key]]
        ],
        [
            'type' => 'changed',
            'check' => fn ($node_key, $first_assoc, $second_assoc) =>
            $first_assoc[$node_key] != $second_assoc[$node_key],
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) =>
            ['key' => $node_key, 'type' =>  $type, 'value_before' =>
            $first_assoc[$node_key], 'value_after' => $second_assoc[$node_key]]
        ]
    ];


    foreach ($node_types as $node_type) {
        ['type' => $type, 'check' => $check, 'process' => $process] = $node_type;
        if ($check($node_key, $first_assoc, $second_assoc)) {
            return ['type' => $type, 'process' => $process];
        }
    }

    return [];
}






function get_type_file($path_to_file)
{
    if (preg_match('/\.([a-z\d]+)$/i', $path_to_file, $matches)) {
        $data_type = $matches[1];
    } else {
        throw new \Exception("file type undefined, current path is '{$path_to_file}'");
    }
    return $data_type;
}

function is_absolute_path($path)
{
    return $path[0] === '/';
}

function get_content($path_to_file)
{
    $current_working_directory = posix_getcwd();
    $absolute_path = is_absolute_path($path_to_file) ? $path_to_file : "{$current_working_directory}/{$path_to_file}";

    $data = file_get_contents($absolute_path);

    if (!$data) {
        throw new \Exception("No such file or directory {$absolute_path}");
    }
    return $data;
}

function converting_data_to_assoc($data)
{
    $resault = [];
    foreach ($data as $key => $val) {
        ['name' => $name, 'process' => $process] = get_property_action($val);
        $resault[$key] = $process($val, 'parse');
    };
    return $resault;
}

function get_property_action($property)
{
    $property_actions = [
        [
            'name' => 'children',
            'check' => fn ($prop) => gettype($prop) === "object",
            'process' => fn ($children, $f) => ['children' =>  converting_data_to_assoc($children)]
            /* 'process' => fn ($children, $f) => ['children' =>  parse($children)]
            почему то не работет =(   Uncaught Error: Call to undefined function parse() */
        ],
        [
            'name' => 'value',
            'check' => fn ($prop) => gettype($prop) !== "object",
            'process' => fn ($prop, $f) => $prop
        ]
    ];

    foreach ($property_actions as $property_action) {
        ['name' => $name, 'check' => $check, 'process' => $process] = $property_action;
        if ($check($property)) {
            return ['name' => $name, 'process' => $process];
        }
    }
}
