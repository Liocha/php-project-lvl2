<?php

namespace Differ\Formatters\PlainPrinter;

function plain_print_tree($resault_tree, $parent_chain = '', $resault = '')
{
    foreach ($resault_tree as $name => $node) {
        [$node_status, $node_deep] = $node['meta'];
        if ($node_deep === 1) {
            $parent_chain = '';
        }
        if (have_chilsdren($node)) {
            if ($node_status === 'old') {
                $resault = "{$resault}Property '" . get_parent_chain($parent_chain, $name) . "' was removed\n";
            } elseif ($node_status === 'new') {
                $resault = "{$resault}Property '" . get_parent_chain($parent_chain, $name) .
                    "' was added with value: 'complex value'\n";
            } else {
                $resault = $resault . plain_print_tree(get_children($node), $parent_chain . $name);
            }
        } else {
            if ($node_status === 'old') {
                $tmp = "Property '" . get_parent_chain($parent_chain, $name) . "' was removed\n";
                $resault = $resault . $tmp;
            } elseif ($node_status === 'new') {
                $new_val = fix_bol_val($node['new_val']);
                $tmp = "Property '" . get_parent_chain($parent_chain, $name) . "' was added with value: '{$new_val}'\n";
                $resault = $resault . $tmp;
            } elseif ($node_status === 'mod') {
                $new_val = fix_bol_val($node['new_val']);
                $old_val = fix_bol_val($node['old_val']);
                $tmp = "Property '" . get_parent_chain($parent_chain, $name) .
                    "' was changed. From '{$old_val}' to '{$new_val}'\n";
                $resault = $resault . $tmp;
            }
        }
    }

    return $resault;
}

function have_chilsdren($node)
{
    return  count($node['children']) > 0;
}

function get_children($node)
{
    return $node['children'];
}

function get_parent_chain($parent_chain, $name)
{
    return $parent_chain === '' ? $name : "{$parent_chain}.{$name}";
}

function fix_bol_val($val)
{
    if (gettype($val) === 'boolean') {
        $val = $val ? 'true' : 'false';
    }
    return $val;
}
