<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;

class GenDiffTest extends TestCase
{

    protected $pathToFixtures = "./tests/fixtures/";

    /**
     * @dataProvider additionProvider
     */

    public function testGenDiff($firstPath, $secondPath, $format, $expected)
    {
        $this->assertEquals($expected, genDiff($firstPath, $secondPath, $format));
    }

    public function additionProvider()
    {
        $expectedPretty = file_get_contents($this->pathToFixtures . 'pretty.txt');
        $expectedPlain = file_get_contents($this->pathToFixtures . 'plain.txt');
        $expectedJson = file_get_contents($this->pathToFixtures . 'json.txt');

        $beforeJson = $this->pathToFixtures . 'before2.json';
        $afterJson = $this->pathToFixtures . 'after2.json';

        $beforeYml = $this->pathToFixtures . 'before2.json';
        $afterYml = $this->pathToFixtures . 'after2.json';

        return [
          [$beforeJson, $afterJson, 'pretty', $expectedPretty],
          [$beforeJson, $afterJson, 'plain', $expectedPlain],
          [$beforeJson, $afterJson, 'json', $expectedJson],
          [$beforeYml, $afterYml, 'pretty', $expectedPretty],
          [$beforeYml, $afterYml, 'plain', $expectedPlain],
          [$beforeYml, $afterYml, 'json', $expectedJson]
        ];
    }
}
