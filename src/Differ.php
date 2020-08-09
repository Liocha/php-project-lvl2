<?php

namespace Differ\Differ;

use Symfony\Component\Yaml\Yaml;

use function Differ\Formatters\PrettyPrinter\pretty_print_tree;
use function Differ\Formatters\PlainPrinter\plain_print_tree;

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

    $diff = gen_diff($path_to_first_file, $path_to_second_file, $format);

    echo ($diff);
}


function gen_diff($path_to_first_file, $path_to_second_file, $format = null)
{
    $data_from_first_file = get_content($path_to_first_file);
    $data_from_second_file = get_content($path_to_second_file);

    $type_first_file = get_type_file($path_to_first_file);
    $type_second_file = get_type_file($path_to_second_file);

    $first_file_assoc =  converting_data_to_accoc($data_from_first_file, $type_first_file);
    $second_file_assoc = converting_data_to_accoc($data_from_second_file, $type_second_file);

    $diff_tree  = create_diff_tree($first_file_assoc);
    $updated_diff_tree  = update_diff_tree($diff_tree, $second_file_assoc);

    return  get_resault_by_format($updated_diff_tree, $format);
}


function get_resault_by_format($resault_tree, $format)
{
    switch ($format) {
        case 'pretty':
            return pretty_print_tree($resault_tree);
        case 'plain':
            return plain_print_tree($resault_tree);
        default:
            throw new \Exception("Unknown output format, current value is {$format}");
    }
}


function converting_data_to_accoc($data, $type)
{
    switch ($type) {
        case 'json':
            return json_decode($data, true);
        case 'yml':
            return Yaml::parse($data);
        case 'yuml':
            return Yaml::parse($data);
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

function create_diff_tree($assoc, $deep = 1)
{
    $resault = [];

    foreach ($assoc as $key => $val) {
        if (is_array($val)) {
            $resault[$key] = ['meta' => ['old', $deep],  'children' => create_diff_tree($val, $deep + 1)];
        } else {
            $resault[$key] = ['meta' => ['old', $deep], 'old_val' => $val, 'children' => []];
        };
    }

    return $resault;
}

function update_diff_tree($resault, $json_file_assoc, $deep = 1)
{
    foreach ($json_file_assoc as $key => $val) {
        if (isset($resault[$key])) {
            if (is_array($val) && count($resault[$key]['children']) > 0) {
                $resault[$key] = array_merge(
                    $resault[$key],
                    ['meta' => ['eql', $deep]],
                    ['children' =>  update_diff_tree(
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
                    ['children' =>  update_diff_tree([], $val, $deep + 1)]
                );
            }

            if (!is_array($val) && count($resault[$key]['children']) === 0) {
                $resault[$key] = $resault[$key]['old_val'] === $val ?
                    array_merge($resault[$key], ['meta' => ['eql', $deep]], ['new_val' =>  $val]) :
                    array_merge($resault[$key], ['meta' => ['mod', $deep]], ['new_val' =>  $val]);
            }
        } else {
            if (is_array($val)) {
                $resault[$key] = ['meta' => ['new', $deep],  'children' => update_diff_tree(
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
