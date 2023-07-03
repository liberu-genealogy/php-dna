<?php declare(strict_types=1);

namespace DnaTest;

// use Dna\SNPs;
// use Dna\Utils\gzip_file;
// use Dna\Utils\zip_file;
use PHPUnit\Framework\TestCase;

abstract class BaseSNPsTestCase extends TestCase
{
    public function simulate_snps(
        $chrom = "1",
        $pos_start = 1,
        $pos_max = 248140902,
        $pos_step = 100,
        $genotype = "AA",
        $insert_nulls = true,
        $null_snp_step = 101,
        $complement_genotype_one_chrom = false,
        $complement_genotype_two_chroms = false,
        $complement_snp_step = 50
    ) {
        // Test implementation
    }
}
