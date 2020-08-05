<?php

namespace Differ\Differ;

use Symfony\Component\Yaml\Yaml;

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

    $format = $args["--format"];
    $path_to_first_file = $args["<firstFile>"];
    $path_to_second_file = $args["<secondFile>"];

    $diff = "{\n" . genDiff($path_to_first_file, $path_to_second_file, $format) . "}\n";

    echo ($diff);
}


function genDiff($path_to_first_file, $path_to_second_file, $format = null)
{
    $data_from_first_file = get_content($path_to_first_file);
    $data_from_second_file = get_content($path_to_second_file);

    if ($format === 'yml') {
        $first_file_assoc = Yaml::parse($data_from_first_file);
        $second_file_assoc = Yaml::parse($data_from_second_file);
    } elseif ($format === 'json') {
        $first_file_assoc = json_decode($data_from_first_file, true);
        $second_file_assoc = json_decode($data_from_second_file, true);
    }

    $resault_tree  = create_tree($first_file_assoc);
    $updated_resault_tree  = update_resault_tree($resault_tree, $second_file_assoc);

    return  get_resault($updated_resault_tree);
}

function update_resault_tree($resault, $json_file_assoc, $deep = 1)
{
    foreach ($json_file_assoc as $key => $val) {
        if (isset($resault[$key])) {
            if (is_array($val) && count($resault[$key]['children']) > 0) {
                $resault[$key] = array_merge(
                    $resault[$key],
                    ['meta' => ['eql', $deep]],
                    ['children' =>  update_resault_tree(
                        $resault[$key]['children'],
                        $val,
                        $deep + 1
                    )]
                );
            }

            if (!is_array($val) && count($resault[$key]['children']) > 0) {
                $resault[$key] = array_merge($resault[$key], ['meta' => ['mod', $deep]], ['new_val' =>  $val]);
            }

            if (is_array($val) && count($resault[$key]['children']) === 0) {
                $resault[$key] = array_merge(
                    $resault[$key],
                    ['meta' => ['mod', $deep]],
                    ['children' =>  update_resault_tree([], $val, $deep + 1)]
                );
            }

            if (!is_array($val) && count($resault[$key]['children']) === 0) {
                $resault[$key] = $resault[$key]['old_val'] === $val ?
                    array_merge($resault[$key], ['meta' => ['eql', $deep]], ['new_val' =>  $val]) :
                    array_merge($resault[$key], ['meta' => ['mod', $deep]], ['new_val' =>  $val]);
            }
        } else {
            if (is_array($val)) {
                $resault[$key] = ['meta' => ['new', $deep],  'children' => update_resault_tree(
                    [],
                    $val,
                    $deep + 1
                )];
            } else {
                $resault[$key] = ['meta' => ['new', $deep], 'new_val' => $val, 'children' => []];
            };
        }
    }

    return $resault;
}


function create_tree($assoc, $deep = 1)
{
    $resault = [];

    foreach ($assoc as $key => $val) {
        if (is_array($val)) {
            $resault[$key] = ['meta' => ['old', $deep],  'children' => create_tree($val, $deep + 1)];
        } else {
            $resault[$key] = ['meta' => ['old', $deep], 'old_val' => $val, 'children' => []];
        };
    }

    return $resault;
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

function get_resault($resault_tree, $mod = true, $resault = '')
{

    foreach ($resault_tree as $key => $val) {
        if (count($val['children']) > 0 &&  $val['meta'][0] !== 'mod') {
            $resault  = $resault . get_ident($val['meta'][1]) . get_sign($val['meta'][0]) .
                $key . ": {\n" . get_resault($val['children'], false) . get_ident($val['meta'][1]) . "  }\n";
        } elseif (count($val['children']) > 0) {
            $resault  = $resault . get_ident($val['meta'][1]) . get_sign($val['meta'][0]) .
                $key . ": {\n" . get_resault($val['children']) . get_ident($val['meta'][1])  . "  }\n";
        } else {
            $resault = $resault . node_to_string($key, $val, $mod);
        }
    }

    return $resault;
}

function node_to_string($name, $node, $mod)
{
    [$meta_sign, $meta_deep] = $node['meta'];



    $ident = get_ident($meta_deep);
    $sign =  $mod ? get_sign($meta_sign) : '  ';
    $tmp =  $meta_sign === 'eql' ? 'old_val' : "{$meta_sign}_val";

    if ($meta_sign === 'mod') {
        $let  = "{$ident}- {$name}: " . fix_bol_val($node['old_val']) . "\n{$ident}+ {$name}: " .
            fix_bol_val($node['new_val']) . "\n";
    } else {
        $let  = "{$ident}{$sign}{$name}: " . fix_bol_val($node[$tmp]) . "\n";
    }
    return $let;
}

function fix_bol_val($val)
{
    if (gettype($val) === 'boolean') {
        $val = $val ? 'true' : 'false';
    }
    return $val;
}


function get_sign($meta_data)
{
    switch ($meta_data) {
        case 'eql':
            return '  ';
        case 'old':
            return '- ';
        case 'new':
            return '+ ';
        case 'mod':
            return '+ ';
        default:
            throw new \Exception('Unknown meta data ' . $meta_data);
    }
}

function get_ident($deep)
{
    $base = '  ';
    $resault = '';
    while ($deep * 2 > 0) {
        $resault = $resault . $base;
        $deep -= 1;
    }
    return $resault;
}
