<?php

namespace Differ\Differ;

use Symfony\Component\Yaml\Yaml;

use function Differ\Formatters\PrettyPrinter\pretty_render;
use function Differ\Formatters\PlainPrinter\plain_render;
use function Differ\Parsers\parse;

function run()
{
    $doc = <<<DOC
		Generate diff.
		
		Usage:
			gendiff (-h|--help)
			gendiff (-v|--version)
			gendiff [--format <fmt>] <firstFile> <secondFile>
		
		Options:
			-h --help                     Show this screen
			-v --version                  Show version
			--format <fmt>                Report format [default: pretty]
		DOC;

    $args = \Docopt::handle($doc, array('version' => "0.0.1"));

    $format = mb_strtolower($args["--format"]);

    $path_to_first_file = $args["<firstFile>"];
    $path_to_second_file = $args["<secondFile>"];


    $resault = gen_diff($path_to_first_file, $path_to_second_file, $format);

    echo $resault;
}

function gen_diff($path_to_first_file, $path_to_second_file, $format)
{
    $data_from_first_file = get_content($path_to_first_file);
    $data_from_second_file = get_content($path_to_second_file);


    $type_first_file = get_type_file($path_to_first_file);
    $type_second_file = get_type_file($path_to_second_file);

    $first_file_obj =  converting_data_to_obj($data_from_first_file, $type_first_file);
    $second_file_obj = converting_data_to_obj($data_from_second_file, $type_second_file);

    $first_file_assoc = parse($first_file_obj);
    $second_file_assoc = parse($second_file_obj);

    $dif_tree =  build_diff_tree($first_file_assoc, $second_file_assoc);

    return render_by_format($dif_tree, $format);
}

function build_diff_tree($first_file_assoc, $second_file_assoc)
{
    $all_node_names = array_unique(array_merge(array_keys($first_file_assoc), array_keys($second_file_assoc)));

    return array_map(function ($node_key) use ($first_file_assoc, $second_file_assoc) {
        ['type' => $type, 'process' => $process] = create_node($node_key, $first_file_assoc, $second_file_assoc);
        if ($type === 'nested') {
            return ['key' => $node_key, 'type' => $type, 'children' =>  build_diff_tree($first_file_assoc[$node_key]['children'], $second_file_assoc[$node_key]['children'])];
        }
        return $process($node_key, $first_file_assoc, $second_file_assoc, $type);
    }, $all_node_names);


    /*     return array_map(function ($node_key) use ($first_file_assoc, $second_file_assoc) {
        ['type' => $type, 'process' => $process] = create_node($node_key, $first_file_assoc, $second_file_assoc);
        if ($type === 'nested') {
            return ['key' => $node_key, 'type' => $type, 'children' =>  build_diff_tree($first_file_assoc[$node_key]['children'], $second_file_assoc[$node_key]['children'])];
        }
        return $process($node_key, $first_file_assoc, $second_file_assoc, $type);
    }, $all_node_names); */
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
            'check' => fn ($node_key, $first_assoc, $second_assoc) => array_key_exists($node_key, $first_assoc) && !array_key_exists($node_key, $second_assoc),
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) => ['key' => $node_key, 'type' => $type, 'value_before' => $first_assoc[$node_key]]
            /* 'process' => fn ($node_key, $first_assoc, $second_assoc, $type) => ['key' => $node_key, 'type' => $type, 'value_before' => array_key_exists('value', $first_assoc[$node_key]) ?  $first_assoc[$node_key]['value'] : $first_assoc[$node_key]['children']] */
        ],
        [
            'type' => 'added',
            'check' => fn ($node_key, $first_assoc, $second_assoc) => !array_key_exists($node_key, $first_assoc) && array_key_exists($node_key, $second_assoc),
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) => ['key' => $node_key, 'type' => $type, 'value_after' => $second_assoc[$node_key]]
            /* 'process' => fn ($node_key, $first_assoc, $second_assoc, $type) => ['key' => $node_key, 'type' => $type, 'value_after' => array_key_exists('value', $second_assoc[$node_key]) ?  $second_assoc[$node_key]['value'] :  dd($second_assoc[$node_key])] */
        ],
        [
            'type' => 'changed',
            'check' => function ($node_key, $first_assoc, $second_assoc) {
                if (gettype($first_assoc[$node_key]) !==  gettype($second_assoc[$node_key])) {
                    return true;
                }

                return false;
            },
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) => ['key' => $node_key, 'type' =>  $type, 'value_before' => $first_assoc[$node_key], 'value_after' => $second_assoc[$node_key]]
        ],
        [
            'type' => 'nested',
            'check' => function ($node_key, $first_assoc, $second_assoc) {
                if (is_array($first_assoc[$node_key]) && is_array($second_assoc[$node_key])) {
                    return array_key_exists('children', $first_assoc[$node_key]) && array_key_exists('children', $second_assoc[$node_key]);
                } else {
                    return false;
                }
            },
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) => ['key' => $node_key, 'type' => $type, 'children' =>  [$first_assoc['children'], $second_assoc['children']]]
        ],
        [
            'type' => 'unchanged',
            'check' => fn ($node_key, $first_assoc, $second_assoc) => $first_assoc[$node_key] === $second_assoc[$node_key],
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) => ['key' => $node_key, 'type' =>  $type, 'value_before' => $first_assoc[$node_key]]
        ],
        [
            'type' => 'changed',
            'check' => fn ($node_key, $first_assoc, $second_assoc) => $first_assoc[$node_key] != $second_assoc[$node_key],
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) => ['key' => $node_key, 'type' =>  $type, 'value_before' => $first_assoc[$node_key], 'value_after' => $second_assoc[$node_key]]
        ]
        /*         [
            'type' => 'changed',
            'check' => fn ($node_key, $first_assoc, $second_assoc) => array_key_exists('children', $first_assoc[$node_key]) && !array_key_exists('children', $second_assoc[$node_key]),
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) => ['key' => $node_key, 'type' =>  $type, 'value_before' => $first_assoc[$node_key]['children'], 'value_after' => $second_assoc[$node_key]['value']]
        ], */
        /*         [
            'type' => 'changed',
            'check' => fn ($node_key, $first_assoc, $second_assoc) => !array_key_exists('children', $first_assoc[$node_key]) && array_key_exists('children', $second_assoc[$node_key]),
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) => ['key' => $node_key, 'type' =>  $type, 'value_before' => $first_assoc[$node_key]['value'], 'value_after' => $second_assoc[$node_key]['children']]
        ],
        [
            'type' => 'unchanged',
            'check' => fn ($node_key, $first_assoc, $second_assoc) => $first_assoc[$node_key]['value']  === $second_assoc[$node_key]['value'],
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) => ['key' => $node_key, 'type' =>  $type, 'value_before' => $first_assoc[$node_key]['value']]
        ],
        [
            'type' => 'changed',
            'check' => fn ($node_key, $first_assoc, $second_assoc) => $first_assoc[$node_key]['value'] != $second_assoc[$node_key]['value'],
            'process' => fn ($node_key, $first_assoc, $second_assoc, $type) => ['key' => $node_key, 'type' =>  $type, 'value_before' => $first_assoc[$node_key]['value'], 'value_after' => $second_assoc[$node_key]['value']]
        ] */
    ];


    foreach ($node_types as $node_type) {
        ['type' => $type, 'check' => $check, 'process' => $process] = $node_type;
        if ($check($node_key, $first_assoc, $second_assoc)) {
            return ['type' => $type, 'process' => $process];
        }
    }

    return [];
}

function render_by_format($diff_tree, $format)
{
    switch ($format) {
        case 'pretty':
            return pretty_render($diff_tree);
        case 'plain':
            return plain_render($diff_tree);
        default:
            throw new \Exception("Unknown output format, current value is {$format}");
    }
}


function converting_data_to_obj($data, $type)
{
    switch ($type) {
        case 'json':
            return json_decode($data);
        case 'yml':
            return Yaml::parse($data, Yaml::PARSE_OBJECT_FOR_MAP);
        case 'yuml':
            return Yaml::parse($data, Yaml::PARSE_OBJECT_FOR_MAP);
        default:
            throw new \Exception("type '{$type}' not supported");
    }
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
