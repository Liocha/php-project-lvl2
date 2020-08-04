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
    dump($format);

    $diff = genDiff($path_to_first_file, $path_to_second_file, $format);

    echo ($diff);
}


function genDiff($path_to_first_file, $path_to_second_file, $format = null)
{
    $data_from_first_file = get_content($path_to_first_file);
    $data_from_second_file = get_content($path_to_second_file);

    if ($format === 'yml') {
        $first_file_assoc = Yaml::parse($data_from_first_file, Yaml::PARSE_OBJECT_FOR_MAP);
        $second_file_assoc = Yaml::parse($data_from_second_file, Yaml::PARSE_OBJECT_FOR_MAP);
    } elseif ($format === 'json') {
        $first_file_assoc = json_decode($data_from_first_file, true);
        $second_file_assoc = json_decode($data_from_second_file, true);
    }


    $resault_tree  = create_resault_tree($first_file_assoc);

    $updated_resault_tree  = update_resault_tree($resault_tree, $second_file_assoc);

    return  get_resault($updated_resault_tree);
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

function create_resault_tree($json_file_assoc)
{
    $resault = [];
    foreach ($json_file_assoc as $key => $val) {
        $resault[$key] = [false, $val, null];
    }

    return $resault;
}

function update_resault_tree($resault, $json_file_assoc)
{
    foreach ($json_file_assoc as $key => $val) {
        if (isset($resault[$key])) {
            [, $old_val] =  $resault[$key];

            if ($old_val === $val) {
                $resault[$key] = [null, $old_val, null];
            } else {
                $new_val = $val;
                $resault[$key] = [true, $old_val, $new_val];
            }
        } else {
            $resault[$key] = [true, $val, null];
        }
    }

    return $resault;
}

function get_resault($resault_tree)
{
    $resault = '';
    $resault = $resault . "\n" . "{" . "\n";
    foreach ($resault_tree as $key => $val) {
        [$modified, $old_val, $new_val] = $val;

        if (gettype($old_val) === 'boolean') {
            $old_val = $old_val ? 'true' : 'false';
        }

        if (gettype($new_val) === 'boolean') {
            $new_val = $new_val ? 'true' : 'false';
        }

        if ($modified === null) {
            $resault = $resault . "    {$key}: {$old_val}\n";
            continue;
        }

        if ($modified === false) {
            $resault = $resault . "  - {$key}: {$old_val}\n";
            continue;
        }

        $resault = $new_val === null ?
            $resault . "  + {$key}: {$old_val}\n" :
            $resault . "  - {$key}: {$old_val}\n  + {$key}: {$new_val}\n";
    }
    $resault = $resault . "}" . "\n";

    return $resault;
}
