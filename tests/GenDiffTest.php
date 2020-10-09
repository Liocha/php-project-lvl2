<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;

function getFixturePath($name)
{
    return implode("/", [__DIR__, "fixtures", $name ]);
}


class GenDiffTest extends TestCase
{
    /**
     * @dataProvider additionProvider
     */

    public function testGenDiff($first, $second, $format, $expected)
    {
        $pathToExpectedFixture = getFixturePath($expected);
        $this->assertStringEqualsFile(
            $pathToExpectedFixture,
            genDiff(getFixturePath($first), getFixturePath($second), $format)
        );
    }

    public function additionProvider()
    {
        return [
            ['before.json', 'after.json', 'pretty', 'pretty.txt'],
            ['before.json', 'after.json', 'plain', 'plain.txt'],
            ['before.json', 'after.json', 'json', 'json.txt'],
            ['before.yml', 'after.yml', 'pretty', 'pretty.txt'],
            ['before.yml', 'after.yml', 'plain', 'plain.txt'],
            ['before.yml', 'after.yml', 'json', 'json.txt']
        ];
    }
}
