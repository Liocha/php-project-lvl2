<?php

namespace Differ\Parsers;

function parse($data)
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
            'process' => fn ($children, $f) => ['children' =>  parse($children)]
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
