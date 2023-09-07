<?php

declare(strict_types=1);

namespace DnaTest\Snps;

use Dna\Snps\SNPs;

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

    public function testMergeRemapFalse() {
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

    
}
