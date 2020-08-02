<?php

namespace Testing;

use PHPUnit\Framework\TestCase;

class GenDiffTest extends TestCase
{
    public function testGenDiff()
    {
        $expected = <<<'EXP'

        {
            host: hexlet.io
          - timeout: 50
          + timeout: 20
          - proxy: 123.234.53.22
          + verbose: true
        }
        
        EXP;
        $this->assertSame($expected, \Differ\Differ\genDiff('./tests/fixtures/before.json', './tests/fixtures/after.json'));
    }
}
