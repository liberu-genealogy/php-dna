<?php

declare(strict_types=1);

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

    public function test__toString()
    {
        $s = new SNPs("tests/input/GRCh37.csv");
        $this->assertEquals("SNPs('GRCh37.csv')", $s->__toString());
    }

    public function test__toString_bytes()
    {
        $data = file_get_contents("tests/input/GRCh37.csv");
        $s = new SNPs($data);
        $this->assertEquals("SNPs(<bytes>)", $s->__toString());
    }

    public function testAssembly()
    {
        $s = new SNPs("tests/input/GRCh38.csv");
        $this->assertEquals($s->getAssembly(), "GRCh38");
    }

    public function testAssemblyNoSnps()
    {
        $emptySnps = $this->empty_snps();

        foreach ($emptySnps as $snps) {
            $this->assertEmpty($snps->getAssembly());
        }
    }

    public function testBuild()
    {
        $s = new SNPs("tests/input/NCBI36.csv");
        $this->assertEquals($s->getBuild(), 36);
        $this->assertEquals($s->getAssembly(), "NCBI36");
    }

    public function testBuildDetectedNoSnps()
    {
        $emptySnps = $this->empty_snps();

        foreach ($emptySnps as $snps) {
            $this->assertFalse($snps->isBuildDetected());
        }
    }

    public function testBuildNoSnps()
    {
        $emptySnps = $this->empty_snps();

        foreach ($emptySnps as $snps) {
            $this->assertEmpty($snps->getBuild());
        }
    }

    public function testBuildDetectedPARSnps()
    {
        $snps = $this->loadAssignPARSnps('tests/input/GRCh37_PAR.csv');
        $this->assertEquals(37, $snps->getBuild());
        $this->assertTrue($snps->isBuildDetected());
        $expectedSnps = $this->snps_GRCh37_PAR();
        $actualSnps = $snps->getSnps();
        $this->assertEquals($expectedSnps, $actualSnps);
    }


    public function test_notnull()
    {
        $s = new SNPs("tests/input/generic.csv");
        $snps = $this->generic_snps();
        unset($snps["rs5"]);

        $this->assertEquals($s->notnull(), $snps, "Frames are not equal!");
    }

    public function test_heterozygous()
    {
        $s = new SNPs("tests/input/generic.csv");

        $expected = $this->create_snp_df(
            rsid: ["rs6", "rs7", "rs8"],
            chrom: ["1", "1", "1"],
            pos: [106, 107, 108],
            genotype: ["GC", "TC", "AT"]
        );

        $this->assertEquals($expected, $s->heterozygous(), "Frames are not equal!");
    }

    public function test_homozygous()
    {
        $s = new SNPs("tests/input/generic.csv");

        $expected = $this->create_snp_df(
            rsid: ["rs1", "rs2", "rs3", "rs4"],
            chrom: ["1", "1", "1", "1"],
            pos: [101, 102, 103, 104],
            genotype: ["AA", "CC", "GG", "TT"],
        );

        $this->assertEquals($expected, $s->homozygous(), "Frames are not equal!");
    }

    public function test_homozygous_chrom()
    {
        $s = new SNPs("tests/input/generic.csv");

        $expected = $this->create_snp_df(
            rsid: ["rs1", "rs2", "rs3", "rs4"],
            chrom: ["1", "1", "1", "1"],
            pos: [101, 102, 103, 104],
            genotype: ["AA", "CC", "GG", "TT"],
        );

        $this->assertEquals($expected, $s->homozygous("1"), "Frames are not equal!");
    }

    public function test_valid_False()
    {
        foreach ($this->empty_snps() as $snps) {
            $this->assertFalse($snps->isValid());
        }
    }

    public function test_valid_True()
    {
        $s = new SNPs("tests/input/generic.csv");
        $this->assertTrue($s->isValid());
    }
}
