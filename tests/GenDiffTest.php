<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;

class GenDiffTest extends TestCase
{

    protected $pathToFixtures = __DIR__ . "/fixtures/";

    /**
     * @dataProvider additionProvider
     */

    public function testGenDiff($firstPath, $secondPath, $format, $expected)
    {
        $resault =  file_get_contents($this->pathToFixtures . $expected);
        $this->assertEquals(
            $resault,
            genDiff($this->pathToFixtures . $firstPath, $this->pathToFixtures . $secondPath, $format)
        );
    }

    public function additionProvider()
    {
        return [
            ['before2.json', 'after2.json', 'pretty', 'pretty.txt'],
            ['before2.json', 'after2.json', 'plain', 'plain.txt'],
            ['before2.json', 'after2.json', 'json', 'json.txt'],
            ['before2.yml', 'after2.yml', 'pretty', 'pretty.txt'],
            ['before2.yml', 'after2.yml', 'plain', 'plain.txt'],
            ['before2.yml', 'after2.yml', 'json', 'json.txt']
        ];
    }
}
