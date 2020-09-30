<?php

namespace Differ\Differ;

use function Differ\Formatters\Formatters\renderByFormat;
use function Differ\Parsers\parse;
use function Funct\Collection\union;

function genDiff($pathToFirstFile, $pathToSecondFile, $format)
{
    $firstFileData = getFileData($pathToFirstFile);
    $secondFileData = getFileData($pathToSecondFile);

    $dataBefore =  parse($firstFileData[0], $firstFileData[1]);
    $dataAfter = parse($secondFileData[0], $secondFileData[1]);

    $difTree =  buildDiffTree($dataBefore, $dataAfter);

    return renderByFormat($difTree, $format);
}

function buildDiffTree($first, $second)
{
    $allNodeNames = union(array_keys(get_object_vars($first)), array_keys(get_object_vars($second)));

    return array_map(function ($nodeKey) use ($first, $second) {
        $node = [];
        $node['key'] = $nodeKey;
        if (!property_exists($second, $nodeKey)) {
            $node['type'] = 'removed';
            $node['valueBefore'] = mappingObjToAssoc($first->$nodeKey);
            return $node;
        };
        if (!property_exists($first, $nodeKey)) {
            $node['type'] = 'added';
            $node['valueAfter'] = mappingObjToAssoc($second->$nodeKey);
            return $node;
        };
        if (is_object($first->$nodeKey) && is_object($second->$nodeKey)) {
            $node['type'] = 'nested';
            $node['children'] = buildDiffTree($first->$nodeKey, $second->$nodeKey);
            return $node;
        }
        if ($first->$nodeKey !== $second->$nodeKey) {
            $node['type'] = 'changed';
            $node['valueBefore'] = mappingObjToAssoc($first->$nodeKey);
            $node['valueAfter'] = mappingObjToAssoc($second->$nodeKey);
            return $node;
        }
        $node['type'] = 'unchanged';
        $node['valueBefore'] = mappingObjToAssoc($first->$nodeKey);
        return $node;
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
    ['extension' => $extension, 'dirname' => $dirName, 'basename' => $baseName] = pathinfo($pathToFile);

    $filePath = realpath("{$dirName}/{$baseName}");

    if (!file_exists($filePath)) {
        throw new \Exception("File '$filePath' does not exist");
    }

    $data = file_get_contents($filePath);

    return [$data, $extension];
}
