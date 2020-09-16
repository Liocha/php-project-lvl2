<?php

namespace Differ\Differ;

use function Differ\Formatters\Formatters\renderByFormat;
use function Differ\Parsers\parse;
use function Funct\Collection\union;

function genDiff($pathToFirstFile, $pathToSecondFile, $format)
{
    $firstFileData = getFileData($pathToFirstFile);
    $secondFileData = getFileData($pathToSecondFile);

    $firstFileObj =  parse($firstFileData);
    $secondFileObj = parse($secondFileData);

    $difTree =  buildDiffTree($firstFileObj, $secondFileObj);

    return renderByFormat($difTree, $format);
}

function buildDiffTree($first, $second)
{
    $allNodeNames = union(array_keys(get_object_vars($first)), array_keys(get_object_vars($second)));

    return array_map(function ($nodeKey) use ($first, $second) {
        if (!property_exists($second, $nodeKey)) {
            $node = new \stdClass();
            $node->key = $nodeKey;
            $node->type = 'removed';
            $node->valueBefore = $first->$nodeKey;
            return $node;
        };
        if (!property_exists($first, $nodeKey)) {
            $node = new \stdClass();
            $node->key = $nodeKey;
            $node->type = 'added';
            $node->valueAfter = $second->$nodeKey;
            return $node;
        };
        if (is_object($first->$nodeKey) && is_object($second->$nodeKey)) {
            $node = new \stdClass();
            $node->key = $nodeKey;
            $node->type = 'nested';
            $node->children = buildDiffTree($first->$nodeKey, $second->$nodeKey);
            return $node;
        }
        if ($first->$nodeKey !== $second->$nodeKey) {
            $node = new \stdClass();
            $node->key = $nodeKey;
            $node->type = 'changed';
            $node->valueBefore = $first->$nodeKey;
            $node->valueAfter = $second->$nodeKey;
            return $node;
        }
        $node = new \stdClass();
        $node->key = $nodeKey;
        $node->type = 'unchanged';
        $node->valueBefore = $first->$nodeKey;
        return $node;
    }, $allNodeNames);
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
