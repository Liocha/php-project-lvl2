<?php

namespace Differ\Differ;

use function Differ\Formatters\render;
use function Differ\Parsers\parse;
use function Funct\Collection\union;

function genDiff($pathToFirstFile, $pathToSecondFile, $format)
{
    [$firstFileData, $firstFileFormat] = getFileData($pathToFirstFile);
    [$secondFileData, $secondFileFormat] = getFileData($pathToSecondFile);

    $dataBefore =  parse($firstFileData, $firstFileFormat);
    $dataAfter = parse($secondFileData, $secondFileFormat);

    $difTree =  buildDiffTree($dataBefore, $dataAfter);

    return render($difTree, $format);
}

function buildDiffTree($first, $second)
{
    $allNodeNames = union(array_keys(get_object_vars($first)), array_keys(get_object_vars($second)));

    return array_map(function ($nodeKey) use ($first, $second) {

        if (!property_exists($second, $nodeKey)) {
            return ['key' => $nodeKey, 'type' => 'removed', 'valueBefore' => $first->$nodeKey];
        };
        if (!property_exists($first, $nodeKey)) {
            return ['key' => $nodeKey, 'type' => 'added', 'valueAfter' => $second->$nodeKey];
        };
        if (is_object($first->$nodeKey) && is_object($second->$nodeKey)) {
            return [
                'key' => $nodeKey,
                'type' => 'nested',
                'children' =>  buildDiffTree($first->$nodeKey, $second->$nodeKey)
            ];
        }
        if ($first->$nodeKey !== $second->$nodeKey) {
            return [
                'key' => $nodeKey,
                'type' => 'changed',
                'valueBefore' => $first->$nodeKey,
                'valueAfter' => $second->$nodeKey
            ];
        }
        return ['key' => $nodeKey, 'type' => 'unchanged', 'valueBefore' => $first->$nodeKey];
    }, $allNodeNames);
}

function getFileData($pathToFile)
{
    ['extension' => $extension] = pathinfo($pathToFile);

    if (!file_exists($pathToFile)) {
        throw new \Exception("File '$pathToFile' does not exist");
    }

    $data = file_get_contents($pathToFile);

    return [$data, $extension];
}
