<?php

namespace Differ\Tests;

use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;

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
        $this->assertSame($expected, genDiff('./tests/fixtures/before.json', './tests/fixtures/after.json'));
    }
}
