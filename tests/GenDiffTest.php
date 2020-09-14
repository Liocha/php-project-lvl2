<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;

class GenDiffTest extends TestCase
{
    private $expected;
    private $expected2;
    private $expectedByPlain;

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

        $this->expected2 = <<<'EXP'
        {
            common: {
                setting1: Value 1
              - setting2: 200
              - setting3: true
              + setting3: {
                    key: value
                }
                setting6: {
                    key: value
                    doge: {
                      - wow: too much
                      + wow: so much
                    }
                  + ops: vops
                }
              + follow: false
              + setting4: blah blah
              + setting5: {
                    key5: value5
                }
            }
            group1: {
              - baz: bas
              + baz: bars
                foo: bar
              - nest: {
                    key: value
                }
              + nest: str
            }
          - group2: {
                abc: 12345
                deep: {
                    id: 45
                }
            }
          + group3: {
                fee: 100500
                deep: {
                    id: {
                        number: 45
                    }
                }
            }
        }

        EXP;

        $this->expectedByPlain = <<<'EXP'
        Property 'common.setting2' was removed
        Property 'common.setting3' was updated. From 'true' to [complex value]
        Property 'common.setting6.doge.wow' was updated. From 'too much' to 'so much'
        Property 'common.setting6.ops' was added with value: 'vops'
        Property 'common.follow' was added with value: 'false'
        Property 'common.setting4' was added with value: 'blah blah'
        Property 'common.setting5' was added with value: [complex value]
        Property 'group1.baz' was updated. From 'bas' to 'bars'
        Property 'group1.nest' was updated. From [complex value] to 'str'
        Property 'group2' was removed
        Property 'group3' was added with value: [complex value]

        EXP;

        $this->expectedByJson = <<<'EXP'
        {"0":{"key":"common","type":"nested","children":{"0":{"key":"setting1","type":"unchanged","valueBefore":"Value 1"},"1":{"key":"setting2","type":"removed","valueBefore":200},"2":{"key":"setting3","type":"changed","valueBefore":true,"valueAfter":{"key":"value"}},"3":{"key":"setting6","type":"nested","children":{"0":{"key":"key","type":"unchanged","valueBefore":"value"},"1":{"key":"doge","type":"nested","children":[{"key":"wow","type":"changed","valueBefore":"too much","valueAfter":"so much"}]},"3":{"key":"ops","type":"added","valueAfter":"vops"}}},"4":{"key":"follow","type":"added","valueAfter":false},"7":{"key":"setting4","type":"added","valueAfter":"blah blah"},"8":{"key":"setting5","type":"added","valueAfter":{"key5":"value5"}}}},"1":{"key":"group1","type":"nested","children":[{"key":"baz","type":"changed","valueBefore":"bas","valueAfter":"bars"},{"key":"foo","type":"unchanged","valueBefore":"bar"},{"key":"nest","type":"changed","valueBefore":{"key":"value"},"valueAfter":"str"}]},"2":{"key":"group2","type":"removed","valueBefore":{"abc":12345,"deep":{"id":45}}},"5":{"key":"group3","type":"added","valueAfter":{"fee":100500,"deep":{"id":{"number":45}}}}}
        EXP;
    }

    public function testGenDiffByJson()
    {
        $this->assertSame($this->expected, genDiff('./tests/fixtures/before.json', './tests/fixtures/after.json', 'pretty'));
    }

    public function testGenDiffByJsonTwo()
    {
        $this->assertSame($this->expected2, genDiff('./tests/fixtures/before2.json', './tests/fixtures/after2.json', 'pretty'));
    }


    public function testGenDiffByYaml()
    {
        $this->assertSame($this->expected, genDiff('./tests/fixtures/before.yml', './tests/fixtures/after.yml', 'pretty'));
    }

    public function testGenDiffByYamlTwo()
    {
        $this->assertSame($this->expected2, genDiff('./tests/fixtures/before2.yml', './tests/fixtures/after2.yml', 'pretty'));
    }

    public function testGenDiffByJsonToPlainFormat()
    {
        $this->assertSame($this->expectedByPlain, genDiff('./tests/fixtures/before2.json', './tests/fixtures/after2.json', 'plain'));
    }

    public function testGenDiffByJsonToJsonFormat()
    {
        $this->assertSame($this->expectedByJson, genDiff('./tests/fixtures/before2.json', './tests/fixtures/after2.json', 'json'));
    }
}
