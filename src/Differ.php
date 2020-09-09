<?php

namespace Differ\Differ;

use function Differ\Formatters\Formatters\renderByFormat;
use function Differ\Parsers\parse;

function genDiff($pathToFirstFile, $pathToSecondFile, $format)
{
    $firstFileData = getFileData($pathToFirstFile);
    $secondFileData = getFileData($pathToSecondFile);

    $firstFileObj =  parse($firstFileData);
    $secondFileObj = parse($secondFileData);

    $firstFileAssoc = convertingDataToAssoc($firstFileObj);
    $secondFileAssoc = convertingDataToAssoc($secondFileObj);

    $difTree =  buildDiffTree($firstFileAssoc, $secondFileAssoc);

    return renderByFormat($difTree, $format);
}

function buildDiffTree($firstFileAssoc, $secondFileAssoc)
{
    $allNodeNames = array_unique(array_merge(array_keys($firstFileAssoc), array_keys($secondFileAssoc)));

    return array_map(function ($nodeKey) use ($firstFileAssoc, $secondFileAssoc) {
        ['type' => $type, 'process' => $process] = createNode($nodeKey, $firstFileAssoc, $secondFileAssoc);
        if ($type === 'nested') {
            $firstChildren = $firstFileAssoc[$nodeKey]['children'];
            $secondChildren = $secondFileAssoc[$nodeKey]['children'];
            return [
                'key' => $nodeKey,
                'type' => $type,
                'children' => buildDiffTree($firstChildren, $secondChildren)
            ];
        }
        return $process($nodeKey, $firstFileAssoc, $secondFileAssoc, $type);
    }, $allNodeNames);
}

function getValueType($node, $node2)
{
    if (gettype($node) === gettype($node2)) {
        return null;
    } else {
        return null;
    }
}

function createNode($nodeKey, $firstAssoc, $secondAssoc)
{
    $nodeTypes = [
        [
            'type' => 'removed',
            'check' => fn ($nodeKey, $firstAssoc, $secondAssoc) =>
            array_key_exists($nodeKey, $firstAssoc) && !array_key_exists($nodeKey, $secondAssoc),
            'process' => fn ($nodeKey, $firstAssoc, $secondAssoc, $type) =>
            ['key' => $nodeKey, 'type' => $type, 'valueBefore' => $firstAssoc[$nodeKey]]
        ],
        [
            'type' => 'added',
            'check' => fn ($nodeKey, $firstAssoc, $secondAssoc) =>
            !array_key_exists($nodeKey, $firstAssoc) && array_key_exists($nodeKey, $secondAssoc),
            'process' => fn ($nodeKey, $firstAssoc, $secondAssoc, $type) =>
            ['key' => $nodeKey, 'type' => $type, 'valueAfter' => $secondAssoc[$nodeKey]]
        ],
        [
            'type' => 'changed',
            'check' => function ($nodeKey, $firstAssoc, $secondAssoc) {
                if (gettype($firstAssoc[$nodeKey]) !==  gettype($secondAssoc[$nodeKey])) {
                    return true;
                }
                return false;
            },
            'process' => fn ($nodeKey, $firstAssoc, $secondAssoc, $type) =>
            ['key' => $nodeKey, 'type' =>  $type, 'valueBefore' =>
            $firstAssoc[$nodeKey], 'valueAfter' => $secondAssoc[$nodeKey]]
        ],
        [
            'type' => 'nested',
            'check' => function ($nodeKey, $firstAssoc, $secondAssoc) {
                if (is_array($firstAssoc[$nodeKey]) && is_array($secondAssoc[$nodeKey])) {
                    return array_key_exists('children', $firstAssoc[$nodeKey]) &&
                        array_key_exists('children', $secondAssoc[$nodeKey]);
                } else {
                    return false;
                }
            },
            'process' => fn ($nodeKey, $firstAssoc, $secondAssoc, $type) =>
            ['key' => $nodeKey, 'type' => $type, 'children' =>
            [$firstAssoc['children'], $secondAssoc['children']]]
        ],
        [
            'type' => 'unchanged',
            'check' => fn ($nodeKey, $firstAssoc, $secondAssoc) =>
            $firstAssoc[$nodeKey] === $secondAssoc[$nodeKey],
            'process' => fn ($nodeKey, $first_assoc, $secondAssoc, $type) =>
            ['key' => $nodeKey, 'type' =>  $type, 'valueBefore' => $firstAssoc[$nodeKey]]
        ],
        [
            'type' => 'changed',
            'check' => fn ($nodeKey, $firstAssoc, $secondAssoc) =>
            $firstAssoc[$nodeKey] != $secondAssoc[$nodeKey],
            'process' => fn ($nodeKey, $firstAssoc, $secondAssoc, $type) =>
            ['key' => $nodeKey, 'type' =>  $type, 'valueBefore' =>
            $firstAssoc[$nodeKey], 'valueAfter' => $secondAssoc[$nodeKey]]
        ]
    ];


    foreach ($nodeTypes as $nodeType) {
        ['type' => $type, 'check' => $check, 'process' => $process] = $nodeType;
        if ($check($nodeKey, $firstAssoc, $secondAssoc)) {
            return ['type' => $type, 'process' => $process];
        }
    }

    return [];
}

function getFileData($pathToFile)
{
    $currentWorkingDirectory = posix_getcwd();
    ['extension' => $extension, 'dirname' => $dirName, 'basename' => $baseName] = pathinfo($pathToFile);

    $filePath = $dirName[0] === '/' ? "{$dirName}/{$baseName}" :
        "{$currentWorkingDirectory}/{$dirName}/{$baseName}";

    if (!file_exists($filePath)) {
        throw new \Exception("File '$filePath' does not exist");
    }

    $data = file_get_contents($filePath);

    return [$data, $extension];
}

function convertingDataToAssoc($data)
{
    $resault = [];
    foreach ($data as $key => $val) {
        ['name' => $name, 'process' => $process] = getPropertyAction($val);
        $resault[$key] = $process($val, 'parse');
    };
    return $resault;
}

function getPropertyAction($property)
{
    $propertyActions = [
        [
            'name' => 'children',
            'check' => fn ($prop) => gettype($prop) === "object",
            'process' => fn ($children, $f) => ['children' =>  convertingDataToAssoc($children)]
            /* 'process' => fn ($children, $f) => ['children' =>  parse($children)]
            почему то не работет =(   Uncaught Error: Call to undefined function parse() */
        ],
        [
            'name' => 'value',
            'check' => fn ($prop) => gettype($prop) !== "object",
            'process' => fn ($prop, $f) => $prop
        ]
    ];

    foreach ($propertyActions as $propertyAction) {
        ['name' => $name, 'check' => $check, 'process' => $process] = $propertyAction;
        if ($check($property)) {
            return ['name' => $name, 'process' => $process];
        }
    }
}
