<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;

class GenDiffTest extends TestCase
{
    private $expected;
    private $expected_2;

    public function setUp(): void
    {
        $this->expected = <<<'EXP'
            host: hexlet.io
          - timeout: 50
          + timeout: 20
          - proxy: 123.234.53.22
          + verbose: true
        
        EXP;

        $this->expected_2 = <<<'EXP'
            common: {
              setting1: Value 1
              setting2: 200
              setting3: true
            - setting6: {
                key: value
              }
              setting4: blah blah
            + setting5: {
                key5: value5
              }
            }
            group1: {
            - baz: bas
            + baz: bars
              foo: bar
            }
          - group2: {
              abc: 12345
            }
          + group3: {
              fee: 100500
            }
        
        EXP;
    }

    public function testGenDiffByJson()
    {
        $this->assertSame($this->expected, genDiff('./tests/fixtures/before.json', './tests/fixtures/after.json', 'json'));
    }

    public function testGenDiffByJsonTwo()
    {
        $this->assertSame($this->expected_2, genDiff('./tests/fixtures/before2.json', './tests/fixtures/after2.json', 'json'));
    }


    public function testGenDiffByYaml()
    {
        $this->assertSame($this->expected, genDiff('./tests/fixtures/before.yml', './tests/fixtures/after.yml', 'yml'));
    }

    public function testGenDiffByYamlTwo()
    {
        $this->assertSame($this->expected_2, genDiff('./tests/fixtures/before2.yml', './tests/fixtures/after2.yml', 'yml'));
    }
}
