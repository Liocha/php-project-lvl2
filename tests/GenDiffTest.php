<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;

class GenDiffTest extends TestCase
{
    private $expected;

    public function setUp(): void
    {
        $this->expected = <<<'EXP'

        {
            host: hexlet.io
          - timeout: 50
          + timeout: 20
          - proxy: 123.234.53.22
          + verbose: true
        }
        
        EXP;
    }

    public function testGenDiffByJson()
    {
        $this->assertSame($this->expected, genDiff('./tests/fixtures/before.json', './tests/fixtures/after.json', 'json'));
    }


    public function testGenDiffByYaml()
    {
        $this->assertSame($this->expected, genDiff('./tests/fixtures/before.yml', './tests/fixtures/after.yml', 'yml'));
    }
}
