<?php

namespace Differ\Differ;

use function Differ\Formatters\Formatters\renderByFormat;
use function Differ\Parsers\parse;
use function Funct\Collection\union;

function genDiff($pathToFirstFile, $pathToSecondFile, $format)
{
    $firstFileData = getFileData($pathToFirstFile);
    $secondFileData = getFileData($pathToSecondFile);

    $dataBefore =  parse($firstFileData);
    $dataAfter = parse($secondFileData);

    $difTree =  buildDiffTree($dataBefore, $dataAfter);

    return renderByFormat($difTree, $format);
}

function buildDiffTree($first, $second)
{
    $allNodeNames = union(array_keys(get_object_vars($first)), array_keys(get_object_vars($second)));

    return array_map(function ($nodeKey) use ($first, $second) {
        $node = new \stdClass();
        if (!property_exists($second, $nodeKey)) {
            $node->key = $nodeKey;
            $node->type = 'removed';
            $node->valueBefore = $first->$nodeKey;
            return $node;
        };
        if (!property_exists($first, $nodeKey)) {
            $node->key = $nodeKey;
            $node->type = 'added';
            $node->valueAfter = $second->$nodeKey;
            return $node;
        };
        if (is_object($first->$nodeKey) && is_object($second->$nodeKey)) {
            $node->key = $nodeKey;
            $node->type = 'nested';
            $node->children = buildDiffTree($first->$nodeKey, $second->$nodeKey);
            return $node;
        }
        if ($first->$nodeKey !== $second->$nodeKey) {
            $node->key = $nodeKey;
            $node->type = 'changed';
            $node->valueBefore = $first->$nodeKey;
            $node->valueAfter = $second->$nodeKey;
            return $node;
        }
        $node->key = $nodeKey;
        $node->type = 'unchanged';
        $node->valueBefore = $first->$nodeKey;
        return $node;
    }, $allNodeNames);
}

function getFileData($pathToFile)
{
    ['extension' => $extension, 'dirname' => $dirName, 'basename' => $baseName] = pathinfo($pathToFile);

    $filePath = realpath("{$dirName}/{$baseName}");

    if (!file_exists($filePath)) {
        throw new \Exception("File '$filePath' does not exist");
    }

    $data = file_get_contents($filePath);

    return [$data, $extension];
}
