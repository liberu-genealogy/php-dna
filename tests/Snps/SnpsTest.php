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

    public static function empty_snps()
    {
        return [new SNPs(), new SNPs(""), new SNPs("tests/input/empty.txt")];
    }

    public function test_len()
    {
        $s = new SNPs("tests/input/generic.csv");
        $this->assertEquals(count($s), 8);
    }

    public function test_len_empty()
    {
        foreach (self::empty_snps() as $s) {
            $this->assertEquals(count($s), 0);
        }
    }
}