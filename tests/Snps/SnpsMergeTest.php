<?php

declare(strict_types=1);

namespace DnaTest\Snps;

use Dna\Snps\SNPs;
use League\Csv\Reader;
use League\Csv\Writer;

class SnpsMergeTest extends SnpsTest
{

    protected function assertResults($results, $expectedResults)
    {
        $this->assertEquals(count($results), count($expectedResults));

        foreach ($results as $i => $result) {
            $expectedResult = $expectedResults[$i];

            $this->assertEquals(
                [
                    "common_rsids",
                    "discrepant_genotype_rsids",
                    "discrepant_position_rsids",
                    "merged",
                ],
                sort(array_keys($result))
            );

            if (array_key_exists("merged", $expectedResult)) {
                if ($expectedResult["merged"]) {
                    $this->assertTrue($result["merged"]);
                } else {
                    $this->assertFalse($result["merged"]);
                }
            } else {
                $this->assertFalse($result["merged"]);
            }

            foreach (["common_rsids", "discrepant_position_rsids", "discrepant_genotype_rsids"] as $key) {
                if (array_key_exists($key, $expectedResult)) {
                    $this->assertEquals(
                        $result[$key],
                        $expectedResult[$key],
                        true,
                        true
                    );
                } else {
                    $this->assertTrue($result[$key]->isEmpty());
                    $this->assertEquals($result[$key]->getName(), "rsid");
                }
            }
        }
    }

    public function testSourceSNPs()
    {
        $tmpdir = sys_get_temp_dir();

        $initial = new SNPs("tests/input/GRCh37.csv", output_dir: $tmpdir);
        $this->assertEquals($initial->getSource(), "generic");
        $initial->merge([new SNPs("tests/input/23andme.txt")]);

        $this->assertEquals($initial->getSource(), "generic, 23andMe");

        $this->assertEquals($initial->getAllSources(), ["generic", "23andMe"]);
        $mergedFile = $tmpdir . "/generic__23andMe_GRCh37.txt";
        $this->assertEquals($initial->toTsv(), $mergedFile);

        $fromFile = new SNPs($mergedFile);


        $this->assertEquals($initial->getSnps(), $fromFile->getSnps());
        $this->assertResults($fromFile, [["merged" => true]]);
    }

    public function testMergeList()
    {
        $s = new SNPs();
        $results = $s->merge([new SNPs("tests/input/GRCh37.csv"), new SNPs("tests/input/GRCh37.csv")]);
        $this->assertEquals($s->getSnps(), self::snps_GRCh37());
        $this->assertEquals($s->getSource(), "generic, generic");
        $this->assertEquals($s->getAllSources(), ["generic", "generic"]);

        $expectedResults = [
            ["merged" => true],
            [
                "merged" => true,
                "common_rsids" => [
                    "rs3094315",
                    "rs2500347",
                    "rsIndelTest",
                    "rs11928389",
                ],
            ],
        ];
        $this->assertResults($results, $expectedResults);
    }

    public function testMergeRemapping()
    {
        $s = new SNPs("tests/input/NCBI36.csv");

        $results = $s->merge([new SNPs("tests/input/GRCh37.csv")]);

        // Check that there are no discrepancies in merge positions and genotypes
        $this->assertCount(0, $s->getDiscrepantMergePositions());
        $this->assertCount(0, $s->getDiscrepantMergeGenotypes());

        // Compare the 'snps' attribute of 's' with the expected array directly
        $this->assertEquals($s->getSnps(), self::snps_NCBI36());

        // Check the results of the merge operation
        $expectedResults = [
            [
                "merged" => true,
                "common_rsids" => [
                    "rs3094315",
                    "rs2500347",
                    "rsIndelTest",
                    "rs11928389",
                ],
            ],
        ];
        $this->assertResults($results, $expectedResults);
    }

    public function testMergeRemapFalse()
    {
        $s = new SNPs("tests/input/NCBI36.csv");

        $results = $s->merge([new SNPs("tests/input/GRCh37.csv")], false);

        // Check the count of discrepancies in merge positions
        $this->assertCount(4, $s->getDiscrepantMergePositions());
        // Compare the discrepancies in merge positions with the expected results
        $this->assertSame(
            $s->getDiscrepantMergePositions(),
            $results[0]["discrepant_position_rsids"]
        );

        // Check the count of discrepancies in merge genotypes
        $this->assertCount(1, $s->getDiscrepantMergeGenotypes());
        // Compare the discrepancies in merge genotypes with the expected results
        $this->assertSame(
            $s->getDiscrepantMergeGenotypes(),
            $results[0]["discrepant_genotype_rsids"]
        );

        // Check the count of discrepancies in both positions and genotypes
        $this->assertCount(4, $s->getDiscrepantMergePositionsGenotypes());
        // Compare the discrepancies in both positions and genotypes with the expected results
        $this->assertSame(
            $s->getDiscrepantMergePositionsGenotypes(),
            $results[0]["discrepant_position_rsids"]
        );

        // Define the expected array for snps_NCBI36 with the discrepant genotype set to null/NA
        $expected = self::snps_NCBI36();
        $expected["rs11928389"]["genotype"] = null;

        // Compare the 'snps' attribute of 's' with the expected array directly
        $this->assertEquals($s->getSnps(), $expected);

        // Check the results of the merge operation
        $expectedResults = [
            [
                "merged" => true,
                "common_rsids" => [
                    "rs3094315",
                    "rs2500347",
                    "rsIndelTest",
                    "rs11928389",
                ],
                "discrepant_position_rsids" => [
                    "rs3094315",
                    "rs2500347",
                    "rsIndelTest",
                    "rs11928389",
                ],
                "discrepant_genotype_rsids" => ["rs11928389"],
            ],
        ];
        $this->assertResults($results, $expectedResults);
    }


    public function testMergePhased()
    {
        $s1 = new SNPs("tests/input/generic.csv");
        $s2 = new SNPs("tests/input/generic.csv");
        $s1->setPhased(true);
        $s2->setPhased(true);

        $results = $s1->merge([$s2]);

        // Check if 's1' is marked as phased
        $this->assertTrue($s1->isPhased());

        // Compare the 'snps' attribute of 's1' with the expected array directly
        $this->assertEquals($s1->getSnps(), self::genericSnps());

        // Check the results of the merge operation
        $expectedResults = [
            [
                "merged" => true,
                "common_rsids" => [
                    "rs1", "rs2", "rs3", "rs4",
                    "rs5", "rs6", "rs7", "rs8"
                ],
            ],
        ];
        $this->assertResults($results, $expectedResults);
    }

    public function testMergeUnphased()
    {
        $s1 = new SNPs("tests/input/generic.csv");
        $s2 = new SNPs("tests/input/generic.csv");
        $s1->setPhased(true);

        $results = $s1->merge([$s2]);

        // Check if 's1' is marked as unphased (not phased)
        $this->assertFalse($s1->isPhased());

        // Compare the 'snps' attribute of 's1' with the expected array directly
        $this->assertEquals($s1->getSnps(), self::genericSnps());

        // Check the results of the merge operation
        $expectedResults = [
            [
                "merged" => true,
                "common_rsids" => [
                    "rs1", "rs2", "rs3", "rs4",
                    "rs5", "rs6", "rs7", "rs8"
                ],
            ],
        ];
        $this->assertResults($results, $expectedResults);
    }

    public function testMergeNonExistentFile()
    {
        $s = new SNPs();
        $results = $s->merge([
            new SNPs("tests/input/non_existent_file.csv"),
            new SNPs("tests/input/GRCh37.csv")
        ]);

        // Compare the 'snps' attribute of 's' with the expected array directly
        $this->assertEquals($s->getSnps(), self::snps_GRCh37());

        // Check the results of the merge operation
        $expectedResults = [
            [], // No merge for the non-existent file
            ["merged" => true],
        ];
        $this->assertResults($results, $expectedResults);
    }

    public function testMergeInvalidFile()
    {
        $s = new SNPs();
        $results = $s->merge([
            new SNPs("tests/input/GRCh37.csv"),
            new SNPs("tests/input/empty.txt")
        ]);

        // Compare the 'snps' attribute of 's' with the expected array directly
        $this->assertEquals($s->getSnps(), self::snps_GRCh37());

        // Check the results of the merge operation
        $expectedResults = [
            ["merged" => true], // Merge with the valid file
            [], // No merge for the invalid file
        ];
        $this->assertResults($results, $expectedResults);
    }

    public function testMergeExceedDiscrepantPositionsThreshold()
    {
        $s1 = new SNPs("tests/input/generic.csv");
        $s2 = new SNPs("tests/input/generic.csv");
        $s2->getSnps()["rs1"]["pos"] = 100;

        $results = $s1->merge([$s2], ["discrepant_positions_threshold" => 0]);
        $this->assertCount(0, $s1->getDiscrepantMergePositions());
        $this->assertCount(0, $s1->getDiscrepantMergeGenotypes());
        $this->assertCount(0, $s1->getDiscrepantMergePositionsGenotypes());

        // Compare the 'snps' attribute of 's1' with the expected array directly
        $this->assertEquals($s1->getSnps(), self::genericSnps());

        // Check the results of the merge operation
        $expectedResults = [[]];
        $this->assertResults($results, $expectedResults);
    }

    public function testMergeExceedDiscrepantGenotypesThreshold()
    {
        $s1 = new SNPs("tests/input/generic.csv");
        $s2 = new SNPs("tests/input/generic.csv");
        $s2->getSnps()["rs1"]["genotype"] = "CC";

        $results = $s1->merge([$s2], ["discrepant_genotypes_threshold" => 0]);
        $this->assertCount(0, $s1->getDiscrepantMergePositions());
        $this->assertCount(0, $s1->getDiscrepantMergeGenotypes());
        $this->assertCount(0, $s1->getDiscrepantMergePositionsGenotypes());

        // Compare the 'snps' attribute of 's1' with the expected array directly
        $this->assertEquals($s1->getSnps(), self::genericSnps());

        // Check the results of the merge operation
        $expectedResults = [[]];
        $this->assertResults($results, $expectedResults);
    }

    public function testMergingFilesDiscrepantSnps()
    {
        $tmpDir = sys_get_temp_dir();
        $dest1 = $tmpDir . "/discrepant_snps1.csv";
        $dest2 = $tmpDir . "/discrepant_snps2.csv";

        // Read the CSV file
        $csv = Reader::createFromPath("tests/input/discrepant_snps.csv", "r");
        $csv->setHeaderOffset(1);
        $records = $csv->getRecords();

        // Create arrays for the first and second CSV files
        $file1Data = [];
        $file2Data = [];
        foreach ($records as $record) {
            $file1Data[] = [
                "chromosome" => $record["chrom"],
                "position" => $record["pos_file1"],
                "genotype" => $record["genotype_file1"],
            ];
            $file2Data[] = [
                "chromosome" => $record["chrom"],
                "position" => $record["pos_file2"],
                "genotype" => $record["genotype_file2"],
            ];
        }

        // Write arrays to CSV files
        $file1Writer = Writer::createFromPath($dest1, "w");
        $file1Writer->insertOne(["chromosome", "position", "genotype"]);
        $file1Writer->insertAll($file1Data);

        $file2Writer = Writer::createFromPath($dest2, "w");
        $file2Writer->insertOne(["chromosome", "position", "genotype"]);
        $file2Writer->insertAll($file2Data);

        $s = new SNPs();
        $s->merge([new SNPs($dest1), new SNPs($dest2)]);

        // Expected data
        $expected = [];
        foreach ($records as $record) {
            $expected[] = [
                "chromosome" => $record["chrom"],
                "discrepant_position" => $record["discrepant_position"],
                "discrepant_genotype" => $record["discrepant_genotype"],
                "pos" => $record["expected_position"],
                "genotype" => $record["expected_genotype"],
            ];
        }

        // Create an SNPs object from the expected data
        $expectedSNPs = new SNPs();
        $expectedSNPs->setSnps($expected);
        $expectedSNPs->sort();
        $expected = $expectedSNPs->getSnps();

        // Assert results
        $this->assertCount(count($expected), $s->getDiscrepantMergePositions());
        $this->assertCount(count($expected), $s->getDiscrepantMergeGenotypes());
        $this->assertArrayHasKey("pos", $s->getSnps());
        $this->assertArrayHasKey("genotype", $s->getSnps());

        // Perform comparisons
        foreach ($expected as $key => $value) {
            $this->assertEquals($value["discrepant_position"], $s->getDiscrepantMergePositions()[$key]);
            $this->assertEquals($value["discrepant_genotype"], $s->getDiscrepantMergeGenotypes()[$key]);
            $this->assertEquals($value["pos"], $s->getSnps()[$key]["pos"]);
            $this->assertEquals($value["genotype"], $s->getSnps()[$key]["genotype"]);
        }
    }

    public function testAppendingDfs()
    {
        $s = new SNPs();
        $s->setSnps([
            ["rsid" => "rs1", "chrom" => "1", "pos" => 1, "genotype" => "AA"],
        ]);
        $s->setDuplicate([
            ["rsid" => "rs1", "chrom" => "1", "pos" => 1, "genotype" => "AA"],
        ]);
        $s->setDiscrepantXY([
            ["rsid" => "rs1", "chrom" => "1", "pos" => 1, "genotype" => "AA"],
        ]);

        $s->merge([$s]);

        $df = [
            ["rsid" => "rs1", "chrom" => "1", "pos" => 1, "genotype" => "AA"],
            ["rsid" => "rs1", "chrom" => "1", "pos" => 1, "genotype" => "AA"],
        ];

        $this->assertEquals($df, $s->getDuplicate());
        $this->assertEquals($df, $s->getDiscrepantXY());
        $this->assertEquals([], $s->getHeterozygousMT());
        $this->assertEquals([], $s->getDiscrepantVcfPosition());
    }

    public function testMergeChrom()
    {
        $s1 = new SNPs("tests/input/generic.csv");
        $s2 = new SNPs();
        $s2->setBuild(37);

        $snpData = [
            ["rsid" => "rs100", "chrom" => "Y", "pos" => 100, "genotype" => "A"],
            ["rsid" => "rs101", "chrom" => "Y", "pos" => 101, "genotype" => null],
            ["rsid" => "rs102", "chrom" => "Y", "pos" => 102, "genotype" => "A"],
            ["rsid" => "rs103", "chrom" => "Y", "pos" => 103, "genotype" => "A"],
        ];

        $s1->setSnps(array_merge($s1->getSnps(), $snpData));
        $s2->setSnps(array_merge($s2->getSnps(), $snpData));

        // Set values for chrom that will be ignored
        $s2->setSnpsValue("rs3", "pos", 1003); // Discrepant position
        $s2->setSnpsValue("rs4", "genotype", "AA"); // Discrepant genotype
        $s2->setSnpsValue("rs5", "genotype", "AA");

        // Set values for chrom to be merged
        $s2->setSnpsValue("rs100", "genotype", "T"); // Discrepant genotype
        $s2->setSnpsValue("rs101", "genotype", "A");
        $s2->setSnpsValue("rs102", "pos", 1002); // Discrepant position

        // Set expected values for merge result
        $s1->setSnpsValue("rs100", "genotype", null); // Discrepant genotype sets to null
        $s1->setSnpsValue("rs101", "genotype", "A"); // Updates null

        $results = $s1->merge([$s2], "Y");

        $this->assertEquals($s1->getSnps(), $s1->getSnps());

        $expectedResults = [
            [
                "merged" => true,
                "common_rsids" => ["rs100", "rs101", "rs102", "rs103"],
                "discrepant_position_rsids" => ["rs102"],
                "discrepant_genotype_rsids" => ["rs100"],
            ]
        ];

        $this->assertEquals($expectedResults, $results);

        $this->assertEquals(count($s1->getDiscrepantMergePositions()), 1);
        $this->assertEquals(count($s1->getDiscrepantMergeGenotypes()), 1);
    }
}
