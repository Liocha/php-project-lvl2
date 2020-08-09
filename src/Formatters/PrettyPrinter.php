<?php

namespace Differ\Formatters\PrettyPrinter;

function pretty_print_tree($resault_diff_tree)
{
    $data =  pretty_print_body_tree($resault_diff_tree);
    return brackets_wrapper($data);
}


function brackets_wrapper($data)
{
    return  "{\n" . $data . "}\n";
}

function pretty_print_body_tree($resault_tree, $mod = true, $resault = '')
{

    foreach ($resault_tree as $key => $val) {
        if (count($val['children']) > 0 &&  $val['meta'][0] !== 'mod') {
            $resault  = $resault . get_ident($val['meta'][1]) . get_sign($val['meta'][0]) .
                $key . ": {\n" . pretty_print_body_tree($val['children'], false) . get_ident($val['meta'][1]) . "  }\n";
        } elseif (count($val['children']) > 0) {
            $resault  = $resault . get_ident($val['meta'][1]) . get_sign($val['meta'][0]) .
                $key . ": {\n" . pretty_print_body_tree($val['children']) . get_ident($val['meta'][1])  . "  }\n";
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
