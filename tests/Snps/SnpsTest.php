<?php declare(strict_types=1);

namespace DnaTest;

use Dna\Snps\SNPs;

class SnpsTest extends BaseSNPsTestCase
{
    // private $table;

    protected function setUp(): void
    {
        // parent::setUp();

        
    }

    // def test___len__(self):
    // s = SNPs("tests/input/generic.csv")
    // self.assertEqual(len(s), 8)
    public function test_len()
    {
        $s = new SNPs("tests/input/generic.csv");
        $this->assertEquals(count($s), 8);
    }
}