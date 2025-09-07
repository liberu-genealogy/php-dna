<?php

declare(strict_types=1);

namespace DnaTest;

use PHPUnit\Framework\TestCase;
use Dna\Snps\SNPs;
use Dna\Individual;

class SNPsTest extends TestCase
{
    private SNPs $snps;

    protected function setUp(): void
    {
        $this->snps = new SNPs();
    }

    public function testEmptySNPs(): void
    {
        $this->assertFalse($this->snps->isValid());
        $this->assertEquals(0, $this->snps->count());
        $this->assertEmpty($this->snps->getSnps());
    }

    public function testSetSnps(): void
    {
        $testData = [
            'rs123' => ['rsid' => 'rs123', 'chrom' => '1', 'pos' => 1000, 'genotype' => 'AA'],
            'rs456' => ['rsid' => 'rs456', 'chrom' => '2', 'pos' => 2000, 'genotype' => 'AT'],
        ];

        $this->snps->setSnps($testData);

        $this->assertTrue($this->snps->isValid());
        $this->assertEquals(2, $this->snps->count());
        $this->assertEquals($testData, $this->snps->getSnps());
    }

    public function testBuildDetection(): void
    {
        // Test with known SNP positions for build detection
        $testData = [
            'rs3094315' => ['rsid' => 'rs3094315', 'chrom' => '1', 'pos' => 752566, 'genotype' => 'AA'], // Build 37
        ];

        $this->snps->setSnps($testData);
        $build = $this->snps->detect_build();

        $this->assertEquals(37, $build);
    }

    public function testSexDetermination(): void
    {
        // Test female determination (heterozygous X chromosome)
        $femaleData = [
            'rs1' => ['rsid' => 'rs1', 'chrom' => 'X', 'pos' => 1000, 'genotype' => 'AT'],
            'rs2' => ['rsid' => 'rs2', 'chrom' => 'X', 'pos' => 2000, 'genotype' => 'CG'],
        ];

        $this->snps->setSnps($femaleData);
        $sex = $this->snps->determine_sex();
        $this->assertEquals('Female', $sex);

        // Test male determination (homozygous X chromosome)
        $maleData = [
            'rs3' => ['rsid' => 'rs3', 'chrom' => 'X', 'pos' => 3000, 'genotype' => 'AA'],
            'rs4' => ['rsid' => 'rs4', 'chrom' => 'X', 'pos' => 4000, 'genotype' => 'TT'],
        ];

        $this->snps->setSnps($maleData);
        $sex = $this->snps->determine_sex();
        $this->assertEquals('Male', $sex);
    }

    public function testChromosomeCounting(): void
    {
        $testData = [
            'rs1' => ['rsid' => 'rs1', 'chrom' => '1', 'pos' => 1000, 'genotype' => 'AA'],
            'rs2' => ['rsid' => 'rs2', 'chrom' => '1', 'pos' => 2000, 'genotype' => 'AT'],
            'rs3' => ['rsid' => 'rs3', 'chrom' => 'X', 'pos' => 3000, 'genotype' => 'AA'],
        ];

        $this->snps->setSnps($testData);

        $this->assertEquals(2, $this->snps->get_count('1'));
        $this->assertEquals(1, $this->snps->get_count('X'));
        $this->assertEquals(3, $this->snps->get_count());
    }

    public function testHeterozygousHomozygous(): void
    {
        $testData = [
            'rs1' => ['rsid' => 'rs1', 'chrom' => '1', 'pos' => 1000, 'genotype' => 'AA'], // Homozygous
            'rs2' => ['rsid' => 'rs2', 'chrom' => '1', 'pos' => 2000, 'genotype' => 'AT'], // Heterozygous
            'rs3' => ['rsid' => 'rs3', 'chrom' => '1', 'pos' => 3000, 'genotype' => 'TT'], // Homozygous
        ];

        $this->snps->setSnps($testData);

        $heterozygous = $this->snps->heterozygous('1');
        $homozygous = $this->snps->homozygous('1');

        $this->assertEquals(1, count($heterozygous));
        $this->assertEquals(2, count($homozygous));
        $this->assertArrayHasKey('rs2', $heterozygous);
        $this->assertArrayHasKey('rs1', $homozygous);
        $this->assertArrayHasKey('rs3', $homozygous);
    }

    public function testSorting(): void
    {
        $testData = [
            'rs3' => ['rsid' => 'rs3', 'chrom' => '2', 'pos' => 1000, 'genotype' => 'AA'],
            'rs1' => ['rsid' => 'rs1', 'chrom' => '1', 'pos' => 2000, 'genotype' => 'AT'],
            'rs2' => ['rsid' => 'rs2', 'chrom' => '1', 'pos' => 1000, 'genotype' => 'TT'],
            'rs4' => ['rsid' => 'rs4', 'chrom' => 'X', 'pos' => 500, 'genotype' => 'GG'],
        ];

        $this->snps->setSnps($testData);
        $this->snps->sort();

        $sortedSnps = $this->snps->getSnps();
        $keys = array_keys($sortedSnps);

        // Should be sorted by chromosome then position
        // Expected order: rs2 (chr1:1000), rs1 (chr1:2000), rs3 (chr2:1000), rs4 (chrX:500)
        $this->assertEquals('rs2', $keys[0]);
        $this->assertEquals('rs1', $keys[1]);
        $this->assertEquals('rs3', $keys[2]);
        $this->assertEquals('rs4', $keys[3]);
    }

    public function testGetAssembly(): void
    {
        $this->snps->setBuild(37);
        $this->assertEquals('GRCh37', $this->snps->getAssembly());

        $this->snps->setBuild(38);
        $this->assertEquals('GRCh38', $this->snps->getAssembly());

        $this->snps->setBuild(36);
        $this->assertEquals('NCBI36', $this->snps->getAssembly());
    }

    public function testGetSummary(): void
    {
        $testData = [
            'rs1' => ['rsid' => 'rs1', 'chrom' => '1', 'pos' => 1000, 'genotype' => 'AA'],
            'rs2' => ['rsid' => 'rs2', 'chrom' => 'X', 'pos' => 2000, 'genotype' => 'AT'],
        ];

        $this->snps->setSnps($testData);
        $this->snps->setBuild(37);

        $summary = $this->snps->getSummary();

        $this->assertIsArray($summary);
        $this->assertEquals('GRCh37', $summary['assembly']);
        $this->assertEquals(37, $summary['build']);
        $this->assertEquals(2, $summary['count']);
        $this->assertArrayHasKey('chromosomes', $summary);
        $this->assertArrayHasKey('sex', $summary);
    }
}