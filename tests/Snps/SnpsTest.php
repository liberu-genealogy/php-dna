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

//     def test_chromosomes(self):
//     s = SNPs("tests/input/chromosomes.csv")
//     self.assertListEqual(s.chromosomes, ["1", "2", "3", "5", "PAR", "MT"])

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
            rsid: ["rs8001"], chrom: ["X"], pos: [80000001], genotype: ["AC"]
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

    public function testDeduplicateFalse() {
        $snps = new SNPs("tests/input/duplicate_rsids.csv", false);
        $result = $this->create_snp_df(["rs1", "rs1", "rs1"], ["1", "1", "1"], [101, 102, 103], ["AA", "CC", "GG"]);
        $this->assertEquals($result, $snps->snps);
    }

    public function testDeduplicateMTChrom() {
        $snps = new SNPs("tests/input/ancestry_mt.txt");
        $result = $this->create_snp_df(["rs1", "rs2"], ["MT", "MT"], [101, 102], ["A", null]);
        $this->assertEquals($result, $snps->snps);

        $heterozygousMTSnps = $this->create_snp_df(["rs3"], ["MT"], [103], ["GC"]);
        $this->assertEquals($heterozygousMTSnps, $snps->heterozygous_MT);
    }

    public function testDeduplicateMTChromFalse() {
        $snps = new SNPs("tests/input/ancestry_mt.txt", false);
        $result = $this->create_snp_df(["rs1", "rs2", "rs3"], ["MT", "MT", "MT"], [101, 102, 103], ["AA", null, "GC"]);
        $this->assertEquals($result, $snps->snps);
    }

    public function testDuplicateRsids() {
        $snps = new SNPs("tests/input/duplicate_rsids.csv");
        $result = $this->create_snp_df(["rs1"], ["1"], [101], ["AA"]);
        $duplicate = $this->create_snp_df(["rs1", "rs1"], ["1", "1"], [102, 103], ["CC", "GG"]);
        $this->assertEquals($result, $snps->snps);
        $this->assertEquals($duplicate, $snps->duplicate);
    }

    




    
}
