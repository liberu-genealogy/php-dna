<?php declare(strict_types=1);

namespace DnaTest\Snps;

use Dna\Snps\SNPs;

class SnpsTest extends BaseSNPsTestCase
{
    // private $table;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_len()
    {
        $s = new SNPs("tests/input/generic.csv");
        $this->assertEquals(count($s), 8);
    }
}