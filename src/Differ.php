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

    $difTree =  buildDiffTree($firstFileObj, $secondFileObj);

    return renderByFormat($difTree, $format);
}

function buildDiffTree($firstFileObj, $secondFileObj)
{
    $allNodeNames = array_unique(array_merge(array_keys((array) $firstFileObj), array_keys((array) $secondFileObj)));

    return array_map(function ($nodeKey) use ($firstFileObj, $secondFileObj) {
        ['type' => $type, 'process' => $process] = createNode($nodeKey, $firstFileObj, $secondFileObj);
        if ($type === 'nested') {
            $obj = new \stdClass();
            $obj->key = $nodeKey;
            $obj->type = 'nested';
            $obj->children = buildDiffTree($firstFileObj->$nodeKey, $secondFileObj->$nodeKey);
            return $obj;
        }
        return $process($nodeKey, $firstFileObj, $secondFileObj, $type);
    }, $allNodeNames);
}

function createNode($nodeKey, $firstFileObj, $secondFileObj)
{
    $nodeTypes = [
        [
            'type' => 'removed',
            'check' => fn ($nodeKey, $firstFileObj, $secondFileObj) =>
            property_exists($firstFileObj, $nodeKey) && !property_exists($secondFileObj, $nodeKey),
            'process' => function ($nodeKey, $firstFileObj, $secondFileObj, $type) {
                $obj = new \stdClass();
                $obj->key = $nodeKey;
                $obj->type = $type;
                $obj->valueBefore = $firstFileObj->$nodeKey;
                return $obj;
            }

        ],
        [
            'type' => 'added',
            'check' => fn ($nodeKey, $firstFileObj, $secondFileObj) =>
            !property_exists($firstFileObj, $nodeKey) && property_exists($secondFileObj, $nodeKey),
            'process' => function ($nodeKey, $firstFileObj, $secondFileObj, $type) {
                $obj = new \stdClass();
                $obj->key = $nodeKey;
                $obj->type = $type;
                $obj->valueAfter = $secondFileObj->$nodeKey;
                return $obj;
            }
        ],
        [
            'type' => 'changed',
            'check' => function ($nodeKey, $firstFileObj, $secondFileObj) {
                return gettype($firstFileObj->$nodeKey) !==  gettype($secondFileObj->$nodeKey) ? true : false;
            },
            'process' => function ($nodeKey, $firstFileObj, $secondFileObj, $type) {
                $obj = new \stdClass();
                $obj->key = $nodeKey;
                $obj->type = $type;
                $obj->valueBefore = $firstFileObj->$nodeKey;
                $obj->valueAfter = $secondFileObj->$nodeKey;
                return $obj;
            }

        ],
        [
            'type' => 'nested',
            'check' => function ($nodeKey, $firstFileObj, $secondFileObj) {
                return is_object($firstFileObj->$nodeKey) && is_object($secondFileObj->$nodeKey) ? true : false;
            },
            'process' => fn ($nodeKey, $firstAssoc, $secondAssoc, $type) =>
            ['key' => $nodeKey, 'type' => $type]
        ],
        [
            'type' => 'unchanged',
            'check' => fn ($nodeKey, $firstFileObj, $secondFileObj) =>
            $firstFileObj->$nodeKey === $secondFileObj->$nodeKey,
            'process' => function ($nodeKey, $firstFileObj, $secondFileObj, $type) {
                $obj = new \stdClass();
                $obj->key = $nodeKey;
                $obj->type = $type;
                $obj->valueBefore = $firstFileObj->$nodeKey;
                return $obj;
            }

        ],
        [
            'type' => 'changed',
            'check' => fn ($nodeKey, $firstFileObj, $secondFileObj) =>
            $firstFileObj->$nodeKey != $secondFileObj->$nodeKey,
            'process' => function ($nodeKey, $firstFileObj, $secondFileObj, $type) {
                $obj = new \stdClass();
                $obj->key = $nodeKey;
                $obj->type = $type;
                $obj->valueBefore = $firstFileObj->$nodeKey;
                $obj->valueAfter = $secondFileObj->$nodeKey;
                return $obj;
            }
        ]
    ];

    foreach ($nodeTypes as $nodeType) {
        ['type' => $type, 'check' => $check, 'process' => $process] = $nodeType;
        if ($check($nodeKey, $firstFileObj, $secondFileObj)) {
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
