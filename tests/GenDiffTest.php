<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;

class GenDiffTest extends TestCase
{

    public function getPath($name)
    {
        return __DIR__ . "/fixtures/" . $name;
    }

    /**
     * @dataProvider additionProvider
     */

    public function testGenDiff($first, $second, $format, $expected)
    {
        $pathToExpectedFixture = $this->getPath($expected);
        $resault =  file_get_contents($pathToExpectedFixture);
        $this->assertStringEqualsFile(
            $pathToExpectedFixture,
            genDiff($this->getPath($first), $this->getPath($second), $format)
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
