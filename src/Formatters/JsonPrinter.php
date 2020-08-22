<?php

namespace Differ\Formatters\JsonPrinter;

function json_print_tree($resault_tree, $parent_chain = '', $resault = [])
{
    foreach ($resault_tree as $name => $node) {
        [$node_status, $node_deep] = $node['meta'];
        if ($node_deep === 1) {
            $parent_chain = '';
        }
        if (have_chilsdren($node)) {
            if ($node_status === 'old') {
                $resault['removed'] = [get_parent_chain($parent_chain, $name) => $node['old_val']];
            } elseif ($node_status === 'new') {
                $resault['added'] = [get_parent_chain($parent_chain, $name) => $node['new_val']];
            } else {
                $resault = array_merge($resault, json_print_tree(get_children($node), $parent_chain . $name));
            }
        } else {
            if ($node_status === 'old') {
                $resault['removed'] = [get_parent_chain($parent_chain, $name) => $node['old_val']];
            } elseif ($node_status === 'new') {
                $resault['added'] = [get_parent_chain($parent_chain, $name) => $node['new_val']];
            } elseif ($node_status === 'mod') {
                $resault['modified'] = [get_parent_chain($parent_chain, $name) => $node['new_val']];
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
    return $parent_chain =  "{$parent_chain}.{$name}";
}

function fix_bol_val($val)
{
    if (gettype($val) === 'boolean') {
        $val = $val ? 'true' : 'false';
    }
    return $val;
}
