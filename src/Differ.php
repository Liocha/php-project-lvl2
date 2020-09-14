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
            $node = new \stdClass();
            $node->key = $nodeKey;
            $node->type = 'nested';
            $node->children = buildDiffTree($firstFileObj->$nodeKey, $secondFileObj->$nodeKey);
            return $node;
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
                $node = new \stdClass();
                $node->key = $nodeKey;
                $node->type = $type;
                $node->valueBefore = $firstFileObj->$nodeKey;
                return $node;
            }

        ],
        [
            'type' => 'added',
            'check' => fn ($nodeKey, $firstFileObj, $secondFileObj) =>
            !property_exists($firstFileObj, $nodeKey) && property_exists($secondFileObj, $nodeKey),
            'process' => function ($nodeKey, $firstFileObj, $secondFileObj, $type) {
                $node = new \stdClass();
                $node->key = $nodeKey;
                $node->type = $type;
                $node->valueAfter = $secondFileObj->$nodeKey;
                return $node;
            }
        ],
        [
            'type' => 'changed',
            'check' => function ($nodeKey, $firstFileObj, $secondFileObj) {
                return gettype($firstFileObj->$nodeKey) !==  gettype($secondFileObj->$nodeKey) ? true : false;
            },
            'process' => function ($nodeKey, $firstFileObj, $secondFileObj, $type) {
                $node = new \stdClass();
                $node->key = $nodeKey;
                $node->type = $type;
                $node->valueBefore = $firstFileObj->$nodeKey;
                $node->valueAfter = $secondFileObj->$nodeKey;
                return $node;
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
                $node = new \stdClass();
                $node->key = $nodeKey;
                $node->type = $type;
                $node->valueBefore = $firstFileObj->$nodeKey;
                return $node;
            }

        ],
        [
            'type' => 'changed',
            'check' => fn ($nodeKey, $firstFileObj, $secondFileObj) =>
            $firstFileObj->$nodeKey != $secondFileObj->$nodeKey,
            'process' => function ($nodeKey, $firstFileObj, $secondFileObj, $type) {
                $node = new \stdClass();
                $node->key = $nodeKey;
                $node->type = $type;
                $node->valueBefore = $firstFileObj->$nodeKey;
                $node->valueAfter = $secondFileObj->$nodeKey;
                return $node;
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
