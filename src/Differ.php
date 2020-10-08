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
        $node = [];
        $node['key'] = $nodeKey;
        if (!property_exists($second, $nodeKey)) {
            return ['key' => $nodeKey, 'type' => 'removed', 'valueBefore' => mappingObjToAssoc($first->$nodeKey)];
        };
        if (!property_exists($first, $nodeKey)) {
            return ['key' => $nodeKey, 'type' => 'added', 'valueAfter' => mappingObjToAssoc($second->$nodeKey)];
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
                'valueBefore' => mappingObjToAssoc($first->$nodeKey),
                'valueAfter' => mappingObjToAssoc($second->$nodeKey)
            ];
        }
        return ['key' => $nodeKey, 'type' => 'unchanged', 'valueBefore' => mappingObjToAssoc($first->$nodeKey)];
    }, $allNodeNames);
}

function mappingObjToAssoc($data)
{
    if (!is_object($data)) {
        return $data;
    }

    $nodeNames = array_keys(get_object_vars($data));
    return ['children' => array_map(
        fn ($item) => ['key' =>  $item, 'value' => mappingObjToAssoc($data->$item)],
        $nodeNames
    )];
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
