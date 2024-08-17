<?php

declare(strict_types=1);

namespace DnaTest\Snps;

use Dna\Snps\SNPs;
use Dna\Snps\SNPData;
use Dna\Snps\SNPAnalyzer;
use Dna\Snps\Analysis\BuildDetector;
use Dna\Snps\Analysis\ClusterOverlapCalculator;
use Dna\Resources;

class SnpsTest extends BaseSNPsTestCase
{
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

    public function test_only_detect_source()
    {
        $s = new SNPs("tests/input/generic.csv", true);
        $this->assertEquals($s->getSource(), "generic");
        $this->assertEquals(count($s), 0);
    }

    public function test_summary()
    {
        $s = new SNPs("tests/input/GRCh38.csv");
        $this->assertEquals(
            $s->getSummary(),
            [
                "source" => "generic",
                "assembly" => "GRCh38",
                "build" => 38,
                "build_detected" => true,
                "count" => 4,
                "chromosomes" => "1, 3",
                "sex" => "",
            ]
        );
    }

    public function test_summary_no_snps()
    {
        foreach ($this->empty_snps() as $snps) {
            $this->assertEquals($snps->getSummary(), []);
        }
    }

    public function test_chromosomes()
    {
        $s = new SNPs("tests/input/chromosomes.csv");
        $this->assertEquals(["1", "2", "3", "5", "PAR", "MT"], $s->getChromosomes());
    }

    public function test_chromosomes_no_snps()
    {
        foreach ($this->empty_snps() as $snps) {
            $this->assertEmpty($snps->getChromosomes());
        }
    }

    public function test_sex_Female_X_chrom()
    {
        $s = $this->simulate_snps(
            chrom: "X",
            pos_start: 1,
            pos_max: 155270560,
            pos_step: 10000,
            genotype: "AC"
        );
        $this->assertEquals("Female", $s->getSex());
    }

    public function test_sex_Female_Y_chrom()
    {
        $s = $this->simulate_snps(
            chrom: "Y",
            pos_start: 1,
            pos_max: 59373566,
            pos_step: 10000,
            null_snp_step: 1
        );
        $this->assertEquals("Female", $s->getSex());
    }

    public function test_sex_Male_X_chrom()
    {
        $s = $this->simulate_snps(
            chrom: "X",
            pos_start: 1,
            pos_max: 155270560,
            pos_step: 10000,
            genotype: "AA"
        );
        $this->assertEquals(15528, $s->count());
        $s->deduplicateXYChrom();
        $this->assertEquals(15528, $s->count());
        $this->assertEquals(0, count($s->getDiscrepantXY()));
        $this->assertEquals("Male", $s->getSex());
    }

    public function test_sex_Male_X_chrom_discrepant_XY()
    {
        $s = $this->simulate_snps(
            chrom: "X",
            pos_start: 1,
            pos_max: 155270560,
            pos_step: 10000,
            genotype: "AA"
        );
        $this->assertEquals(15528, $s->count());
        $s->setValue("rs8001", "genotype", "AC");
        $s->deduplicateXYChrom();
        $this->assertEquals(15527, $s->count());
        $result = $this->create_snp_df(
            rsid: ["rs8001"],
            chrom: ["X"],
            pos: [80000001],
            genotype: ["AC"]
        );
        $this->assertEquals($result, $s->getDiscrepantXY());
        $this->assertEquals("Male", $s->getSex());
    }

    public function test_sex_male_Y_chrom()
    {
        $s = $this->simulate_snps(
            chrom: "Y",
            pos_start: 1,
            pos_max: 59373566,
            pos_step: 10000
        );

        $this->assertEquals("Male", $s->getSex());
    }

    public function test_sex_not_determined()
    {
        $s = $this->simulate_snps(
            chrom: "1",
            pos_start: 1,
            pos_max: 249250621,
            pos_step: 10000
        );

        $this->assertEquals("", $s->getSex());
    }

    public function test_sex_no_snps()
    {
        foreach ($this->empty_snps() as $snps) {
            $this->assertEmpty($snps->getSex());
        }
    }

    public function test_source()
    {
        $s = new SNPs("tests/input/generic.csv");
        $this->assertEquals("generic", $s->getSource());
        $this->assertEquals(["generic"], $s->getAllSources());
    }

    public function test_source_no_snps()
    {
        foreach ($this->empty_snps() as $snps) {
            $this->assertEmpty($snps->getSource());
        }
    }

    public function test_count()
    {
        $s = new SNPs("tests/input/NCBI36.csv");
        $this->assertEquals(4, $s->count());
    }

    public function test_count_no_snps()
    {
        foreach ($this->empty_snps() as $snps) {
            $this->assertEquals(0, $snps->count());
            $this->assertEmpty($snps->getSnps());
        }
    }

    public function testDeduplicateFalse()
    {
        $snps = new SNPs("tests/input/duplicate_rsids.csv", deduplicate: false);
        $result = $this->create_snp_df(["rs1", "rs1", "rs1"], ["1", "1", "1"], [101, 102, 103], ["AA", "CC", "GG"]);
        $this->assertEquals($result, $snps->getSnps());
    }

    public function testDeduplicateMTChrom()
    {
        $snps = new SNPs("tests/input/ancestry_mt.txt");
        $result = $this->create_snp_df(["rs1", "rs2"], ["MT", "MT"], [101, 102], ["A", null]);
        $this->assertEquals($result, $snps->getSnps());

        $heterozygousMTSnps = $this->create_snp_df(["rs3"], ["MT"], [103], ["GC"]);
        $this->assertEquals($heterozygousMTSnps, $snps->getHeterozygousMT());
    }

    public function testDeduplicateMTChromFalse()
    {
        $snps = new SNPs("tests/input/ancestry_mt.txt", deduplicate: false);
        $result = $this->create_snp_df(["rs1", "rs2", "rs3"], ["MT", "MT", "MT"], [101, 102, 103], ["AA", null, "GC"]);
        $this->assertEquals($result, $snps->getSnps());
    }

    public function testDuplicateRsids()
    {
        $snps = new SNPs("tests/input/duplicate_rsids.csv");
        $result = $this->create_snp_df(["rs1"], ["1"], [101], ["AA"]);
        $duplicate = $this->create_snp_df(["rs1", "rs1"], ["1", "1"], [102, 103], ["CC", "GG"]);
        $this->assertEquals($result, $snps->getSnps());
        $this->assertEquals($duplicate, $snps->getDuplicate());
    }

    public function testRemap36to37()
    {
        $this->_run_remap_test(function () {
            $s = new SNPs("tests/input/NCBI36.csv");
            list($chromosomes_remapped, $chromosomes_not_remapped) = $s->remap(37);
            $this->assertEquals(37, $s->getBuild());
            $this->assertEquals("GRCh37", $s->getAssembly());
            $this->assertCount(2, $chromosomes_remapped);
            $this->assertCount(0, $chromosomes_not_remapped);
            $this->assertEquals($this->snps_GRCh37(), $s->getSnps());
        }, $this->NCBI36_GRCh37());
    }

    public function testRemap37to36()
    {
        $this->_run_remap_test(function () {
            $s = new SNPs("tests/input/GRCh37.csv");
            list($chromosomes_remapped, $chromosomes_not_remapped) = $s->remap(36);
            $this->assertEquals(36, $s->getBuild());
            $this->assertEquals("NCBI36", $s->getAssembly());
            $this->assertCount(2, $chromosomes_remapped);
            $this->assertCount(0, $chromosomes_not_remapped);
            $this->assertEquals($this->snps_NCBI36(), $s->getSnps());
        }, $this->GRCh37_NCBI36());
    }

    public function testRemap37to38()
    {
        $this->_run_remap_test(function () {
            $s = new SNPs("tests/input/GRCh37.csv");
            list($chromosomes_remapped, $chromosomes_not_remapped) = $s->remap(38);
            $this->assertEquals(38, $s->getBuild());
            $this->assertEquals("GRCh38", $s->getAssembly());
            $this->assertCount(2, $chromosomes_remapped);
            $this->assertCount(0, $chromosomes_not_remapped);
            $this->assertEquals($this->snps_GRCh38(), $s->getSnps());
        }, $this->GRCh37_GRCh38());
    }

    public function testRemap37to37()
    {
        $s = new SNPs("tests/input/GRCh37.csv");
        list($chromosomes_remapped, $chromosomes_not_remapped) = $s->remap(37);
        $this->assertEquals(37, $s->getBuild());
        $this->assertEquals("GRCh37", $s->getAssembly());
        $this->assertCount(0, $chromosomes_remapped);
        $this->assertCount(2, $chromosomes_not_remapped);
        $this->assertEquals($this->snps_GRCh37(), $s->getSnps());
    }

    public function testRemapInvalidAssembly()
    {
        $s = new SNPs("tests/input/GRCh37.csv");
        list($chromosomes_remapped, $chromosomes_not_remapped) = $s->remap(-1);
        $this->assertEquals(37, $s->getBuild());
        $this->assertEquals("GRCh37", $s->getAssembly());
        $this->assertCount(0, $chromosomes_remapped);
        $this->assertCount(2, $chromosomes_not_remapped);
    }

    public function testRemapNoSnps()
    {
        $s = new SNPs();
        list($chromosomes_remapped, $chromosomes_not_remapped) = $s->remap(38);
        $this->assertFalse($s->getBuild());
        $this->assertCount(0, $chromosomes_remapped);
        $this->assertCount(0, $chromosomes_not_remapped);
    }

    public function testSaveToTsv()
    {
        $s = new SNPs("tests/input/generic.csv");
        $tempFile = tempnam(sys_get_temp_dir(), 'snps_test');
        $s->toTsv($tempFile);
        $content = file_get_contents($tempFile);
        $this->assertStringStartsWith("# Generated by snps", $content);
        unlink($tempFile);
    }

    public function testSaveNoSNPs()
    {
        $s = new SNPs();
        $this->assertFalse($s->toTsv());
    }

    public function testSaveNoSNPsVCF()
    {
        $s = new SNPs();
        $this->assertFalse($s->toVcf());
    }

    public function testSaveSource()
    {
        $tmpdir = sys_get_temp_dir();
        $s = new SNPs("tests/input/GRCh38.csv", outputDir: $tmpdir);
        $dest = $tmpdir . DIRECTORY_SEPARATOR . "generic_GRCh38.txt";
        $this->assertEquals($s->toTsv(), $dest);
        $snps = new SNPs($dest);
        $this->assertEquals($snps->getBuild(), 38);
        $this->assertTrue($snps->isBuildDetected());
        $this->assertEquals($snps->getSource(), "generic");
        $this->assertEquals($snps->getAllSources(), ["generic"]);
        $this->assertEquals($this->snps_GRCh38(), $snps->getSnps());
    }

    public function testCluster()
    {
        $this->runClusterTest(function ($mock) {
            $s = new SNPs("tests/input/23andme.txt", resources: $mock);
            $this->assertEquals($s->getCluster(), "c1");
        }, $this->getChipClusters());
    }

    public function testChip()
    {
        $this->runClusterTest(function ($mock) {
            $s = new SNPs("tests/input/23andme.txt", resources: $mock);
            $this->assertEquals($s->getChip(), "HTS iSelect HD");
        }, $this->_getChipClusters());
    }

    public function testChipVersion()
    {
        $this->runClusterTest(function ($mock) {
            $s = new SNPs("tests/input/23andme.txt", resources: $mock);
            $this->assertEquals($s->getChipVersion(), "v4");
        }, $this->getChipClusters());
    }

    public function testComputeClusterOverlap()
    {
        $this->runClusterTest(function ($mock) {
            $s = new SNPs("tests/input/23andme.txt", resources: $mock);
            $result = $s->computeClusterOverlap();
            $this->assertEquals($s->getCluster(), "c1");
            $this->assertEquals($s->getChip(), "HTS iSelect HD");
            $this->assertEquals($s->getChipVersion(), "v4");
            $this->assertArrayHasKey("c1", $result);
        }, $this->_getChipClusters());
    }

    public function testSnpsQc()
    {
        $s = new SNPs("tests/input/generic.csv");
        $snpsQc = $s->getSnpsQc();
        $expectedQcSnps = $this->genericSnps();
        unset($expectedQcSnps['rs4']);
        unset($expectedQcSnps['rs6']);
        $this->assertEquals($expectedQcSnps, $snpsQc);
    }

    public function testLowQuality()
    {
        $s = new SNPs("tests/input/generic.csv");
        $lowQualitySnps = $s->getLowQualitySnps();
        $expectedLowQualitySnps = $this->genericSnps();
        $this->assertEquals($expectedLowQualitySnps, $lowQualitySnps);
    }

    // Add more tests for SNPData and SNPAnalyzer classes
    public function testSNPData()
    {
        $snpData = new SNPData($this->genericSnps());
        $this->assertEquals(8, $snpData->count());
        $this->assertEquals(["1"], $snpData->getChromosomes());
    }

    public function testSNPAnalyzer()
    {
        $buildDetector = $this->createMock(BuildDetector::class);
        $buildDetector->method('detectBuild')->willReturn(37);

        $clusterOverlapCalculator = $this->createMock(ClusterOverlapCalculator::class);
        $clusterOverlapCalculator->method('computeClusterOverlap')->willReturn(['cluster' => 'c1']);

        $snpAnalyzer = new SNPAnalyzer($buildDetector, $clusterOverlapCalculator);
        $snpData = new SNPData($this->genericSnps());

        $this->assertEquals(37, $snpAnalyzer->detectBuild($snpData));
        $this->assertEquals(['cluster' => 'c1'], $snpAnalyzer->computeClusterOverlap($snpData));
        $this->assertEquals('Female', $snpAnalyzer->determineSex($snpData));
    }
}
