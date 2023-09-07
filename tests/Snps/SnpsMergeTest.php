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

    public function testMergeList() {
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

    
}
