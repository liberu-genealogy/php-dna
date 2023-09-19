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

    public function test_only_detect_source()
    {
        $s = new SNPs("tests/input/generic.csv", true);
        $this->assertEquals($s->getSource(), "generic");
        $this->assertEquals(count($s), 0);
    }

    public function test_empty_dataframe()
    {
        // for snps in self.empty_snps():
        //         self.assertListEqual(
        //             list(snps.snps.columns.values), ["chrom", "pos", "genotype"]
        //         )
        //         self.assertEqual(snps.snps.index.name, "rsid")
        // foreach ($this->empty_snps() as $snps) {
        //     $this->assertEquals(
        //         $snps->getSnps()->columns->toArray(),
        //         ["chrom", "pos", "genotype"]
        //     );
        //     $this->assertEquals($snps->getSnps()->index->name, "rsid");
        // }
    }

    public function test_assembly_None()
    {
        $snps = new SNPs();
        $this->assertFalse($snps->getAssembly());
    }

    //    def test_summary(self):
    //         s = SNPs("tests/input/GRCh38.csv")
    //         self.assertDictEqual(
    //             s.summary,
    //             {
    //                 "source": "generic",
    //                 "assembly": "GRCh38",
    //                 "build": 38,
    //                 "build_detected": True,
    //                 "count": 4,
    //                 "chromosomes": "1, 3",
    //                 "sex": "",
    //             },
    //         )
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
        var_dump($s->getChromosomes());
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

    // def test_sex_Male_X_chrom(self):
    //     s = self.simulate_snps(
    //         chrom="X", pos_start=1, pos_max=155270560, pos_step=10000, genotype="AA"
    //     )
    //     self.assertEqual(s.count, 15528)
    //     s._deduplicate_XY_chrom()
    //     self.assertEqual(s.count, 15528)
    //     self.assertEqual(len(s.discrepant_XY), 0)
    //     self.assertEqual(s.sex, "Male")

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
        $s->deduplicate_XY_chrom();
        $this->assertEquals(15528, $s->count());
        $this->assertEquals(0, count($s->getDiscrepantXY()));
        $this->assertEquals("Male", $s->getSex());
    }

    // def test_sex_Male_X_chrom_discrepant_XY(self):
    //     s = self.simulate_snps(
    //         chrom="X", pos_start=1, pos_max=155270560, pos_step=10000, genotype="AA"
    //     )
    //     self.assertEqual(s.count, 15528)
    //     s._snps.loc["rs8001", "genotype"] = "AC"
    //     s._deduplicate_XY_chrom()
    //     self.assertEqual(s.count, 15527)
    //     result = self.create_snp_df(
    //         rsid=["rs8001"], chrom=["X"], pos=[80000001], genotype=["AC"]
    //     )
    //     pd.testing.assert_frame_equal(s.discrepant_XY, result, check_exact=True)
    //     self.assertEqual(s.sex, "Male")

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
        // $s->getSnps()->loc["rs8001", "genotype"] = "AC";
        $s->setValue("rs8001", "genotype", "AC");
        $s->deduplicate_XY_chrom();
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

    // def test_sex_Male_Y_chrom(self):
    //     s = self.simulate_snps(chrom="Y", pos_start=1, pos_max=59373566, pos_step=10000)
    //     self.assertEqual(s.sex, "Male")

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

    // def test_sex_not_determined(self):
    //     s = self.simulate_snps(
    //         chrom="1", pos_start=1, pos_max=249250621, pos_step=10000
    //     )
    //     self.assertEqual(s.sex, "")

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

    // def test_sex_no_snps(self):
    //     for snps in self.empty_snps():
    //         self.assertFalse(snps.sex)

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
        $this->assertEquals($result, $snps->snps);
    }

    public function testDeduplicateMTChrom()
    {
        $snps = new SNPs("tests/input/ancestry_mt.txt");
        $result = $this->create_snp_df(["rs1", "rs2"], ["MT", "MT"], [101, 102], ["A", null]);
        $this->assertEquals($result, $snps->snps);

        $heterozygousMTSnps = $this->create_snp_df(["rs3"], ["MT"], [103], ["GC"]);
        $this->assertEquals($heterozygousMTSnps, $snps->heterozygous_MT);
    }

    public function testDeduplicateMTChromFalse()
    {
        $snps = new SNPs("tests/input/ancestry_mt.txt", deduplicate: false);
        $result = $this->create_snp_df(["rs1", "rs2", "rs3"], ["MT", "MT", "MT"], [101, 102, 103], ["AA", null, "GC"]);
        $this->assertEquals($result, $snps->snps);
    }

    public function testDuplicateRsids()
    {
        $snps = new SNPs("tests/input/duplicate_rsids.csv");
        $result = $this->create_snp_df(["rs1"], ["1"], [101], ["AA"]);
        $duplicate = $this->create_snp_df(["rs1", "rs1"], ["1", "1"], [102, 103], ["CC", "GG"]);
        $this->assertEquals($result, $snps->getSnps());
        $this->assertEquals($duplicate, $snps->duplicate);
    }

    public function _run_remap_test($f, $mappings)
    {
        if ($this->downloads_enabled) {
            $f();
        } else {
            $mock = $this->createMock(Resources::class);
            $mock->method('get_assembly_mapping_data')->willReturn($mappings);

            $this->getMockBuilder(Resources::class)
                ->setMethods(['get_assembly_mapping_data'])
                ->getMock();

            $f();
        }
    }

    public function test_remap_36_to_37()
    {
        $this->_run_remap_test(function () {
            $s = new SNPs("tests/input/NCBI36.csv");
            list($chromosomes_remapped, $chromosomes_not_remapped) = $s->remap(37);
            $this->assertEquals(37, $s->build);
            $this->assertEquals("GRCh37", $s->assembly);
            $this->assertCount(2, $chromosomes_remapped);
            $this->assertCount(0, $chromosomes_not_remapped);
            $this->assertEquals($this->snps_GRCh37(), $s->getSnps());
        }, $this->NCBI36_GRCh37());
    }

    public function test_remap_36_to_37_multiprocessing()
    {
        $this->_run_remap_test(function () {
            $s = new SNPs("tests/input/NCBI36.csv", true);
            [$chromosomes_remapped, $chromosomes_not_remapped] = $s->remap(37);
            $this->assertEquals(37, $s->build);
            $this->assertEquals("GRCh37", $s->assembly);
            $this->assertCount(2, $chromosomes_remapped);
            $this->assertCount(0, $chromosomes_not_remapped);
            $this->assertSnpsArrayEquals($s->snps, $this->snps_GRCh37(), true);
        }, $this->NCBI36_GRCh37());
    }

    public function test_remap_37_to_36()
    {
        $this->_run_remap_test(function () {
            $s = new SNPs("tests/input/GRCh37.csv");
            [$chromosomes_remapped, $chromosomes_not_remapped] = $s->remap(36);
            $this->assertEquals(36, $s->build);
            $this->assertEquals("NCBI36", $s->assembly);
            $this->assertCount(2, $chromosomes_remapped);
            $this->assertCount(0, $chromosomes_not_remapped);
            $this->assertSnpsArrayEquals($s->snps, $this->snps_NCBI36(), true);
        }, $this->GRCh37_NCBI36());
    }

    public function test_remap_37_to_38()
    {
        $this->_run_remap_test(function () {
            $s = new SNPs("tests/input/GRCh37.csv");
            [$chromosomes_remapped, $chromosomes_not_remapped] = $s->remap(38);
            $this->assertEquals(38, $s->build);
            $this->assertEquals("GRCh38", $s->assembly);
            $this->assertCount(2, $chromosomes_remapped);
            $this->assertCount(0, $chromosomes_not_remapped);
            $this->assertSnpsArrayEquals($s->snps, $this->snps_GRCh38(), true);
        }, $this->GRCh37_GRCh38());
    }

    public function test_remap_37_to_38_with_PAR_SNP()
    {
        $this->_run_remap_test(function () {
            $s = $this->loadAssignPARSNPs("tests/input/GRCh37_PAR.csv");
            $this->assertEquals(4, $s->count);
            [$chromosomes_remapped, $chromosomes_not_remapped] = $s->remap(38);
            $this->assertEquals(38, $s->build);
            $this->assertEquals("GRCh38", $s->assembly);
            $this->assertCount(2, $chromosomes_remapped);
            $this->assertCount(1, $chromosomes_not_remapped);
            $this->assertEquals(3, $s->count);
            $this->assertSnpsArrayEquals($s->snps, $this->snps_GRCh38_PAR(), true);
        }, $this->GRCh37_GRCh38_PAR());
    }

    public function test_remap_37_to_37()
    {
        $s = new SNPs("tests/input/GRCh37.csv");
        [$chromosomes_remapped, $chromosomes_not_remapped] = $s->remap(37);
        $this->assertEquals(37, $s->build);
        $this->assertEquals("GRCh37", $s->assembly);
        $this->assertCount(0, $chromosomes_remapped);
        $this->assertCount(2, $chromosomes_not_remapped);
        $this->assertSnpsArrayEquals($s->snps, $this->snps_GRCh37(), true);
    }

    public function test_remap_invalid_assembly()
    {
        $s = new SNPs("tests/input/GRCh37.csv");
        [$chromosomes_remapped, $chromosomes_not_remapped] = $s->remap(-1);
        $this->assertEquals(37, $s->build);
        $this->assertEquals("GRCh37", $s->assembly);
        $this->assertCount(0, $chromosomes_remapped);
        $this->assertCount(2, $chromosomes_not_remapped);
    }

    public function test_remap_no_snps()
    {
        $s = new SNPs();
        [$chromosomes_remapped, $chromosomes_not_remapped] = $s->remap(38);
        $this->assertFalse($s->build);
        $this->assertCount(0, $chromosomes_remapped);
        $this->assertCount(0, $chromosomes_not_remapped);
    }

    public function testSaveBufferBinary()
    {
        $s = new SNPs("tests/input/generic.csv");
        $out = fopen('php://memory', 'wb');
        $s->toTsv($out);
        rewind($out);
        $this->assertTrue(strpos(stream_get_contents($out), "# Generated by snps") === 0);
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
        $s = new SNPs("tests/input/GRCh38.csv", output_dir: $tmpdir);
        $dest = $tmpdir . DIRECTORY_SEPARATOR . "generic_GRCh38.txt";
        $this->assertEquals($s->toTsv(), $dest);
        $snps = new SNPs($dest);
        $this->assertEquals($snps->build, 38);
        $this->assertTrue($snps->buildDetected);
        $this->assertEquals($snps->source, "generic");
        $this->assertEquals($snps->_source, ["generic"]);
        $this->assertEquals($this->snps_GRCh38(), $snps->getSnps());
    }

    private function makeAncestryAssertions($d)
    {
        $this->assertEquals($d["population_code"], "ITU");
        $this->assertEquals($d["population_description"], "Indian Telugu in the UK");
        $this->assertIsFloat($d["population_percent"]);
        $this->assertGreaterThanOrEqual(0.2992757864426246 - 0.00001, $d["population_percent"]);
        $this->assertLessThanOrEqual(0.2992757864426246 + 0.00001, $d["population_percent"]);
        $this->assertEquals($d["superpopulation_code"], "SAS");
        $this->assertEquals($d["superpopulation_description"], "South Asian Ancestry");
        $this->assertIsFloat($d["superpopulation_percent"]);
        $this->assertGreaterThanOrEqual(0.827977563875996 - 0.00001, $d["superpopulation_percent"]);
        $this->assertLessThanOrEqual(0.827977563875996 + 0.00001, $d["superpopulation_percent"]);
        $this->assertArrayHasKey("predicted_population_population", $d["ezancestry_df"]);
        $this->assertArrayHasKey("predicted_population_superpopulation", $d["ezancestry_df"]);
    }

    // public function testAncestry()
    // {
    //     $ezancestryMods = ["ezancestry", "ezancestry.commands"];
    //     $poppedMods = $this->popModules($ezancestryMods);

    //     if (extension_loaded("ezancestry")) {
    //         // Test with ezancestry if installed
    //         $s = new SNPs("tests/input/generic.csv");
    //         $this->makeAncestryAssertions($s->predictAncestry());
    //     }

    //     // Mock ezancestry modules
    //     foreach ($ezancestryMods as $mod) {
    //         $this->setMockedModule($mod);
    //     }

    //     // Mock the predict function
    //     $mockedData = [
    //         "predicted_population_population" => ["ITU"],
    //         "population_description" => ["Indian Telugu in the UK"],
    //         "ITU" => [0.2992757864426246],
    //         "predicted_population_superpopulation" => ["SAS"],
    //         "superpopulation_name" => ["South Asian Ancestry"],
    //         "SAS" => [0.827977563875996],
    //     ];

    //     $this->setMockedFunction("ezancestry.commands", "predict", $mockedData);

    //     // Test with mocked ezancestry
    //     $s = new SNPs("tests/input/generic.csv");
    //     $this->makeAncestryAssertions($s->predictAncestry());

    //     // Unload mocked ezancestry modules
    //     $this->popModules($ezancestryMods);

    //     // Restore ezancestry modules if ezancestry is installed
    //     $this->restoreModules($poppedMods);
    // }

    public function testAncestryModuleNotFoundError()
    {
        if (!extension_loaded("ezancestry")) {
            // Test when ezancestry is not installed
            $s = new SNPs("tests/input/generic.csv");
            $this->expectException(ModuleNotFoundError::class);
            $this->expectExceptionMessage("Ancestry prediction requires the ezancestry package; please install it using `composer require ezancestry/ezancestry`");
            $s->predictAncestry();
        }
    }

    private function getChipClusters($pos = [101, 102, 103, 104, 105, 106, 107, 108], $cluster = "c1", $length = 8)
    {
        $data = [];
        for ($i = 0; $i < $length; $i++) {
            $data[] = [
                "chrom" => "1",
                "pos" => $pos[$i],
                "clusters" => $cluster
            ];
        }

        return collect($data);
    }

    public function runClusterTest($f, $chipClusters)
    {
        $mock = $this->getMockBuilder(Resources::class)
            ->setMethods(['getChipClusters'])
            ->getMock();

        $mock->method('getChipClusters')
            ->willReturn($chipClusters);

        $this->assertInstanceOf(Resources::class, $mock);
        $f($mock);
    }

    public function testCluster()
    {
        $this->runClusterTest(function ($mock) {
            $s = new SNPs("tests/input/23andme.txt", $mock);
            $this->assertEquals($s->getCluster(), "c1");
        }, $this->getChipClusters());
    }

    public function testChip()
    {
        $this->runClusterTest(function ($mock) {
            $s = new SNPs("tests/input/23andme.txt", $mock);
            $this->assertEquals($s->getChip(), "HTS iSelect HD");
        }, $this->_getChipClusters());
    }

    public function testChipVersion()
    {
        $this->runClusterTest(function ($mock) {
            $s = new SNPs("tests/input/23andme.txt", $mock);
            $this->assertEquals($s->getChipVersion(), "v4");
        }, $this->getChipClusters());
    }

    public function testChipVersionNA()
    {
        $this->runClusterTest(function ($mock) {
            $s = new SNPs("tests/input/myheritage.csv", $mock);
            $this->assertEquals($s->getCluster(), "c3");
            $this->assertEquals($s->getChipVersion(), "");
        }, $this->getChipClusters("c3"));
    }

    public function testComputeClusterOverlapSetPropertyValues()
    {
        $this->runClusterTest(function ($mock) {
            $s = new SNPs("tests/input/23andme.txt", $mock);
            $s->computeClusterOverlap();
            $this->assertEquals($s->getCluster(), "c1");
            $this->assertEquals($s->getChip(), "HTS iSelect HD");
            $this->assertEquals($s->getChipVersion(), "v4");
        }, $this->_getChipClusters());
    }

    public function testComputeClusterOverlapThresholdNotMet()
    {
        $this->runClusterTest(function ($mock) {
            $s = new SNPs("tests/input/23andme.txt", $mock);
            $this->assertEquals($s->getCluster(), "");
        }, $this->_getChipClusters(range(104, 112)));
    }

    public function testComputeClusterOverlapSourceWarning()
    {
        $this->runClusterTest(function ($mock) {
            $s = new SNPs("tests/input/generic.csv", $mock);
            $this->assertEquals($s->getCluster(), "c1");
        }, $this->_getChipClusters());

        $logs = $this->getActualOutput();

        $this->assertStringContainsString(
            "Detected SNPs data source not found in cluster's company composition",
            $logs
        );
    }

    public function testComputeClusterOverlapRemap()
    {
        $this->runClusterTest(function ($mock) {
            $s = new SNPs("tests/input/23andme.txt", $mock);
            // drop SNPs not currently remapped by test mapping data
            $snps = $s->getSnps();
            unset($snps["rs4"]);
            unset($snps["rs5"]);
            unset($snps["rs6"]);
            unset($snps["rs7"]);
            unset($snps["rs8"]);
            $s->setBuild(36);  // manually set build 36
            $this->assertEquals($s->getCluster(), "c1");
            $this->assertEquals($s->getBuild(), 36);  // ensure copy gets remapped
        }, $this->_getChipClusters(["pos" => range(101, 104)], 3));
    }

    public function testSnpsQc()
    {
        // Simulate the creation of your SNP object with the provided CSV data
        $s = new SNPs("tests/input/generic.csv");

        // Identify quality controlled SNPs and get them as an array or other data structure
        $snpsQc = $s->getSnpsQc();

        // Create an array that represents your expected QC SNPs, excluding rs4 and rs6
        $expectedQcSnps = $this->genericSnps();
        unset($expectedQcSnps['rs4']);
        unset($expectedQcSnps['rs6']);

        // Assert that the computed QC SNPs match the expected QC SNPs
        $this->assertEquals($expectedQcSnps, $snpsQc);
    }

    public function testLowQuality()
    {
        // Simulate the creation of your SNP object with the provided CSV data
        $s = new SNPs("tests/input/generic.csv");

        // Identify low-quality SNPs and get them as an array or other data structure
        $lowQualitySnps = $s->getLowQualitySnps();

        // Create an array that represents your expected low-quality SNPs, including rs4 and rs6
        $expectedLowQualitySnps = $this->genericSnps();

        // Assert that the computed low-quality SNPs match the expected low-quality SNPs
        $this->assertEquals($expectedLowQualitySnps, $lowQualitySnps);
    }

    // public function testSnpsQcLowQualityNoCluster() {
    //     function f() {
    //         $s = new SNPs("tests/input/generic.csv");
    //         // Identify low-quality SNPs
    //         $this->assertEquals(
    //             $s->low_quality, 
    //             $this->getLowQualitySnps(['rs4', 'rs6'])
    //         );
    //         // Return already identified low-quality SNPs (test branch)
    //         $this->assertEquals(
    //             $s->low_quality, 
    //             $this->getLowQualitySnps(['rs4', 'rs6'])
    //         );
    //     }
    
    //     $this->runLowQualitySnpsTest('f', $this->getLowQualitySnps(), ['cluster' => '']);
    // }

    // private function testIdentifyLowQualitySnpsRemap() {
    //     $f = function() {
    //         $s = new SNPs("tests/input/generic.csv");
    //         // Drop SNPs not currently remapped by test mapping data
    //         $s->_snps->drop(["rs4", "rs5", "rs6", "rs7", "rs8"], 1);
    //         $s->_build = 36;  // Manually set build 36
    //         $s->identifyLowQualitySnps();
    //         $this->assertEquals($s->snpsQc, $this->getLowQualitySnps(['rs1', 'rs3']));
    //         $this->assertEquals($s->lowQuality, $this->getLowQualitySnps()['rs2']);
    //         $this->assertEquals($s->build, 36);  // Ensure copy gets remapped
    //     }
    
    //     $mock = $this->getMockBuilder('Resources')
    //         ->setMethods(['getAssemblyMappingData'])
    //         ->getMock();
    //     $mock->expects($this->any())
    //         ->method('getAssemblyMappingData')
    //         ->willReturn($this->getTestAssemblyMappingData(
    //             "NCBI36",
    //             "GRCh37",
    //             array_fill(0, 8, 1),
    //             array(101, 101, 102, 102, 103, 103, 0, 0)
    //         ));
    
    //     $this->runLowQualitySnpsTest('f', $this->getLowQualitySnps(array(102, 1001)));
    // }
}
