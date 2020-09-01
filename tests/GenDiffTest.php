<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use function Differ\Differ\gen_diff;

class GenDiffTest extends TestCase
{
    private $expected;
    private $expected_2;
    private $expected_by_plain;

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

        $this->expected_2 = <<<'EXP'
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

        $this->expected_by_plain = <<<'EXP'
        Property 'common.setting2' was removed
        Property 'common.setting6' was removed
        Property 'common.setting4' was added with value: 'blah blah'
        Property 'common.setting5' was added with value: 'complex value'
        Property 'group1.baz' was changed. From 'bas' to 'bars'
        Property 'group2' was removed
        Property 'group3' was added with value: 'complex value'

        EXP;
    }

    public function testGenDiffByJson()
    {
        $this->assertSame($this->expected, gen_diff('./tests/fixtures/before.json', './tests/fixtures/after.json', 'pretty'));
    }

    public function testGenDiffByJsonTwo()
    {
        $this->assertSame($this->expected_2, gen_diff('./tests/fixtures/before2.json', './tests/fixtures/after2.json', 'pretty'));
    }


    public function testGenDiffByYaml()
    {
        $this->assertSame($this->expected, gen_diff('./tests/fixtures/before.yml', './tests/fixtures/after.yml', 'pretty'));
    }

    public function testGenDiffByYamlTwo()
    {
        $this->assertSame($this->expected_2, gen_diff('./tests/fixtures/before2.yml', './tests/fixtures/after2.yml', 'pretty'));
    }

    public function testGenDiffByJsonTwoPlainFormat()
    {
        $this->assertSame($this->expected_by_plain, gen_diff('./tests/fixtures/before2.json', './tests/fixtures/after2.json', 'plain'));
    }
}
