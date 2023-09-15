<?php

declare(strict_types=1);

namespace DnaTest\Snps;

use Dna\Snps\EnsemblRestClient;
use Dna\Snps\SNPs;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

use GuzzleHttp\Psr7\Response;

abstract class BaseSNPsTestCase extends TestCase
{

    protected $downloads_enabled = false;


    public function simulate_snps(
        string $chrom = "1",
        int $pos_start = 1,
        int $pos_max = 248140902,
        int $pos_step = 100,
        string $genotype = "AA",
        bool $insert_nulls = true,
        int $null_snp_step = 101,
        bool $complement_genotype_one_chrom = false,
        bool $complement_genotype_two_chroms = false,
        int $complement_snp_step = 50
    ): SNPs {
        $s = new SNPs();
        $s->setBuild(37);

        $positions = range($pos_start, $pos_max - 1, $pos_step);
        $snps = array_fill_keys(array_map(fn ($x) => "rs" . ($x + 1), range(0, count($positions) - 1)), ["chrom" => $chrom]);
        foreach ($positions as $index => $pos) {
            $snps["rs" . ($index + 1)]["pos"] = $pos;
            $snps["rs" . ($index + 1)]["genotype"] = $genotype;
        }

        if ($insert_nulls) {
            for ($i = 0; $i < count($snps); $i += $null_snp_step) {
                $snps[array_keys($snps)[$i]]["genotype"] = null;
            }
        }

        for ($i = 0; $i < count($snps); $i += $complement_snp_step) {
            $key = array_keys($snps)[$i];
            if ($complement_genotype_two_chroms) {
                $snps[$key]["genotype"] = $this->complement_two_chroms($snps[$key]["genotype"]);
            } elseif ($complement_genotype_one_chrom) {
                $snps[$key]["genotype"] = $this->complement_one_chrom($snps[$key]["genotype"]);
            }
        }

        $s->setSNPs($snps);

        return $s;
    }

    public static function get_complement($base)
    {
        switch ($base) {
            case "A":
                return "T";
            case "G":
                return "C";
            case "C":
                return "G";
            case "T":
                return "A";
            default:
                return $base;
        }
    }

    public function complement_one_chrom($genotype)
    {
        if (is_null($genotype)) {
            return null;
        }

        $complement = "";

        for ($i = 0; $i < strlen($genotype); $i++) {
            $base = $genotype[$i];
            $complement .= static::get_complement($base);
            $complement .= $genotype[1];
            return $complement;
        }

        return $complement;
    }

    public function complement_two_chroms($genotype)
    {
        if (is_null($genotype)) {
            return null;
        }

        $complement = "";

        for ($i = 0; $i < strlen($genotype); $i++) {
            $base = $genotype[$i];
            $complement .= static::get_complement($base);
        }

        return $complement;
    }



    // def run_parsing_tests(
    //     self, file, source, phased=False, build=37, build_detected=False, snps_df=None
    // ):
    //     self.make_parsing_assertions(
    //         self.parse_file(file), source, phased, build, build_detected, snps_df
    //     )
    //     self.make_parsing_assertions(
    //         self.parse_bytes(file), source, phased, build, build_detected, snps_df
    //     )

    //     with tempfile.TemporaryDirectory() as tmpdir:
    //         base = os.path.basename(file)
    //         dest = os.path.join(tmpdir, f"{base}.gz")
    //         gzip_file(file, dest)
    //         self.make_parsing_assertions(
    //             self.parse_file(dest), source, phased, build, build_detected, snps_df
    //         )
    //         self.make_parsing_assertions(
    //             self.parse_bytes(dest), source, phased, build, build_detected, snps_df
    //         )
    //         # remove .gz extension
    //         shutil.move(dest, dest[:-3])
    //         self.make_parsing_assertions(
    //             self.parse_file(dest[:-3]),
    //             source,
    //             phased,
    //             build,
    //             build_detected,
    //             snps_df,
    //         )

    //         dest = os.path.join(tmpdir, f"{base}.zip")
    //         zip_file(file, dest, base)
    //         self.make_parsing_assertions(
    //             self.parse_file(dest), source, phased, build, build_detected, snps_df
    //         )
    //         self.make_parsing_assertions(
    //             self.parse_bytes(dest), source, phased, build, build_detected, snps_df
    //         )
    //         # remove .zip extension
    //         shutil.move(dest, dest[:-4])
    //         self.make_parsing_assertions(
    //             self.parse_file(dest[:-4]),
    //             source,
    //             phased,
    //             build,
    //             build_detected,
    //             snps_df,
    //         )

    // @staticmethod
    // def create_snp_df(rsid, chrom, pos, genotype):
    //     df = pd.DataFrame(
    //         {"rsid": rsid, "chrom": chrom, "pos": pos, "genotype": genotype},
    //         columns=["rsid", "chrom", "pos", "genotype"],
    //     )
    //     df.rsid = df.rsid.astype(object)
    //     df.chrom = df.chrom.astype(object)
    //     df.pos = df.pos.astype(np.uint32)
    //     df.genotype = df.genotype.astype(object)
    //     df = df.set_index("rsid")
    //     return df
    public static function create_snp_df(
        mixed $rsid,
        mixed $chrom,
        mixed $pos,
        mixed $genotype
    ) {
        $df = [];
        foreach ($rsid as $i => $r) {
            $df[] = [
                "rsid" => $r,
                "chrom" => is_array($chrom) ? $chrom[$i] : $chrom,
                "pos" => is_array($pos) ? $pos[$i] : $pos,
                "genotype" => is_array($genotype) ? $genotype[$i] : $genotype,
            ];
        }

        var_dump($df);
        return $df;
    }

    protected function generic_snps()
    {
        $rsid = [];
        for ($i = 1; $i <= 8; $i++) {
            $rsid[] = "rs" . $i;
        }
        return $this->create_snp_df(
            rsid: $rsid,
            chrom: array_fill(0, 8, "1"),
            pos: range(101, 109),
            genotype: ["AA", "CC", "GG", "TT", null, "GC", "TC", "AT"],
        );
    }

    protected function makeNormalizedArrayAssertions($array)
    {
        $this->assertEquals(array_keys($array), array_column($array, 'rsid'));
        // $this->assertTrue($this->isObjectDtype(array_column($array, 'rsid')));
        // $this->assertTrue($this->isObjectDtype(array_column($array, 'chrom')));
        // $this->assertTrue($this->isUnsignedIntegerDtype(array_column($array, 'pos')));
        // $this->assertTrue($this->isObjectDtype(array_column($array, 'genotype')));
    }

    public function run_parse_tests(
        $file,
        $source,
        $phased = false,
        $build = 37,
        $build_detected = false,
        $snps_df = null
    ) {
        $snps = $this->parse_file($file);
        // echo "snps:\n";
        // print_r($snps);

        $this->make_parsing_assertions(
            $snps,
            $source,
            $phased,
            $build,
            $build_detected,
            $snps_df
        );
    }

    // def make_parsing_assertions(
    //     self, snps, source, phased, build, build_detected, snps_df
    // ):
    //     if snps_df is None:
    //         snps_df = self.generic_snps()

    //     # these are useful for debugging if there is a problem
    //     print("Observed:")
    //     print(snps.snps)
    //     print(snps.snps.info())
    //     print("Expected:")
    //     print(snps_df)
    //     print(snps_df.info())

    //     self.assertEqual(snps.source, source)
    //     pd.testing.assert_frame_equal(snps.snps, snps_df, check_exact=True)
    //     self.assertTrue(snps.phased) if phased else self.assertFalse(snps.phased)
    //     self.assertEqual(snps.build, build)
    //     self.assertTrue(snps.build_detected) if build_detected else self.assertFalse(
    //         snps.build_detected
    //     )
    //     self.make_normalized_dataframe_assertions(snps.snps)

    protected function assertSnpsArrayEquals($actual, $expected, $checkExact = false)
    {
        $this->assertEquals(count($expected), count($actual));

        foreach ($expected as $rsid => $expectedSnp) {
            $this->assertArrayHasKey($rsid, $actual);

            $actualSnp = $actual[$rsid];

            if ($checkExact) {
                $this->assertEquals($expectedSnp, $actualSnp);
            } else {
                foreach ($expectedSnp as $key => $value) {
                    $this->assertArrayHasKey($key, $actualSnp);
                    $this->assertEquals($value, $actualSnp[$key]);
                }
            }
        }
    }


    protected function make_parsing_assertions(
        SNPs $snps,
        $source,
        $phased,
        $build,
        $build_detected,
        $snps_df
    ) {
        if ($snps_df === null) {
            $snps_df = $this->generic_snps();
        }

        // These are useful for debugging if there is a problem
        // echo "Observed:\n";
        // echo $snps->isBuildDetected() . "\n";
        // print_r(count($snps->getSnps()));
        // print_r($snps->snps['info']());
        // echo "Expected:\n";
        // echo $build . "\n";
        // print_r(count($snps_df));
        // print_r($snps_df['info']());

        $this->assertEquals($source, $snps->getSource());
        $this->assertEquals($snps_df, $snps->getSnps());
        // $this->assertTrue($snps['phased']) ?: $this->assertFalse($snps['phased']);
        $this->assertEquals($build, $snps->getBuild());
        if ($build_detected) {
            $this->assertTrue($snps->isBuildDetected());
        } else {
            $this->assertFalse($snps->isBuildDetected());
        }
        // $this->makeNormalizedDataframeAssertions($snps['snps']);
    }


    public function parse_file($file, $rsids = [])
    {
        return new SNPs($file, rsids: $rsids);
    }

    protected function _get_test_assembly_mapping_data($source, $target, $strands, $mappings)
    {
        return [
            "1" => [
                "mappings" => [
                    [
                        "original" => [
                            "seq_region_name" => "1",
                            "strand" => $strands[0] ?? "",
                            "start" => $mappings[0] ?? "",
                            "end" => $mappings[0] ?? "",
                            "assembly" => $source,
                        ],
                        "mapped" => [
                            "seq_region_name" => "1",
                            "strand" => $strands[1] ?? "",
                            "start" => $mappings[1] ?? "",
                            "end" => $mappings[1] ?? "",
                            "assembly" => $target,
                        ],
                    ],
                    [
                        "original" => [
                            "seq_region_name" => "1",
                            "strand" => $strands[2] ?? "",
                            "start" => $mappings[2] ?? "",
                            "end" => $mappings[2] ?? "",
                            "assembly" => $source,
                        ],
                        "mapped" => [
                            "seq_region_name" => "1",
                            "strand" => $strands[3] ?? "",
                            "start" => $mappings[3] ?? "",
                            "end" => $mappings[3] ?? "",
                            "assembly" => $target,
                        ],
                    ],
                    [
                        "original" => [
                            "seq_region_name" => "1",
                            "strand" => $strands[4] ?? "",
                            "start" => $mappings[4] ?? "",
                            "end" => $mappings[4] ?? "",
                            "assembly" => $source,
                        ],
                        "mapped" => [
                            "seq_region_name" => "1",
                            "strand" => $strands[5] ?? "",
                            "start" => $mappings[5] ?? "",
                            "end" => $mappings[5] ?? "",
                            "assembly" => $target,
                        ],
                    ],
                ],
            ],
            "3" => [
                "mappings" => [
                    [
                        "original" => [
                            "seq_region_name" => "3",
                            "strand" => $strands[6] ?? "",
                            "start" => $mappings[6] ?? "",
                            "end" => $mappings[6] ?? "",
                            "assembly" => $source,
                        ],
                        "mapped" => [
                            "seq_region_name" => "3",
                            "strand" => $strands[7] ?? "",
                            "start" => $mappings[7] ?? "",
                            "end" => $mappings[7] ?? "",
                            "assembly" => $target,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function createMockHttpClient(array $mockResponses, bool $compress = false): Client
    {
        $mockHandler = new MockHandler($mockResponses);

        $httpClientOptions = [];
        if ($compress) {
            $httpClientOptions['headers'] = ['Content-Encoding' => 'gzip'];
        }

        $handlerStack = HandlerStack::create($mockHandler);
        return new Client(['handler' => $handlerStack, 'http_errors' => false] + $httpClientOptions);
    }


    protected function NCBI36_GRCh37()
    {
        return $this->_get_test_assembly_mapping_data(
            "NCBI36",
            "GRCh37",
            [1, 1, 1, 1, 1, 1, 1, -1],
            [
                742429,
                752566,
                143649677,
                144938320,
                143649678,
                144938321,
                50908372,
                50927009,
            ]
        );
    }

    protected function GRCh37_NCBI36()
    {
        return $this->_get_test_assembly_mapping_data(
            "GRCh37",
            "NCBI36",
            [1, 1, 1, 1, 1, 1, 1, -1],
            [
                752566,
                742429,
                144938320,
                143649677,
                144938321,
                143649678,
                50927009,
                50908372,
            ]
        );
    }

    protected function GRCh37_GRCh38()
    {
        return $this->_get_test_assembly_mapping_data(
            "GRCh37",
            "GRCh38",
            [1, 1, 1, -1, 1, -1, 1, 1],
            [
                752566,
                817186,
                144938320,
                148946169,
                144938321,
                148946168,
                50927009,
                50889578,
            ]
        );
    }

    protected function GRCh37_GRCh38_PAR()
    {
        return [
            "X" => [
                "mappings" => [
                    [
                        "original" => [
                            "seq_region_name" => "X",
                            "strand" => 1,
                            "start" => 220770,
                            "end" => 220770,
                            "assembly" => "GRCh37",
                        ],
                        "mapped" => [
                            "seq_region_name" => "X",
                            "strand" => 1,
                            "start" => 304103,
                            "end" => 304103,
                            "assembly" => "GRCh38",
                        ],
                    ],
                    [
                        "original" => [
                            "seq_region_name" => "X",
                            "strand" => 1,
                            "start" => 91941056,
                            "end" => 91941056,
                            "assembly" => "GRCh37",
                        ],
                        "mapped" => [
                            "seq_region_name" => "X",
                            "strand" => 1,
                            "start" => 92686057,
                            "end" => 92686057,
                            "assembly" => "GRCh38",
                        ],
                    ],
                ]
            ],
            "Y" => [
                "mappings" => [
                    [
                        "original" => [
                            "seq_region_name" => "Y",
                            "strand" => 1,
                            "start" => 535258,
                            "end" => 535258,
                            "assembly" => "GRCh37",
                        ],
                        "mapped" => [
                            "seq_region_name" => "Y",
                            "strand" => 1,
                            "start" => 624523,
                            "end" => 624523,
                            "assembly" => "GRCh38",
                        ],
                    ]
                ]
            ],
        ];
    }

    protected function snps_NCBI36()
    {
        return $this->create_snp_df(
            rsid: ["rs3094315", "rs2500347", "rsIndelTest", "rs11928389"],
            chrom: ["1", "1", "1", "3"],
            pos: [742429, 143649677, 143649678, 50908372],
            genotype: ["AA", null, "ID", "AG"],
        );
    }


    protected function snps_GRCh37()
    {
        return $this->create_snp_df(
            rsid: ["rs3094315", "rs2500347", "rsIndelTest", "rs11928389"],
            chrom: ["1", "1", "1", "3"],
            pos: [752566, 144938320, 144938321, 50927009],
            genotype: ["AA", null, "ID", "TC"],
        );
    }


    protected function snps_GRCh38()
    {
        return $this->create_snp_df(
            rsid: ["rs3094315", "rsIndelTest", "rs2500347", "rs11928389"],
            chrom: ["1", "1", "1", "3"],
            pos: [817186, 148946168, 148946169, 50889578],
            genotype: ["AA", "ID", null, "TC"],
        );
    }


    protected function snps_GRCh37_PAR()
    {
        return $this->create_snp_df(
            rsid: ["rs28736870", "rs113378274", "rs113313554", "rs758419898"],
            chrom: ["X", "X", "Y", "PAR"],
            pos: [220770, 91941056, 535258, 1],
            genotype: ["AA", "AA", "AA", "AA"],
        );
    }

    protected function snps_GRCh38_PAR()
    {
        return $this->create_snp_df(
            rsid: ["rs28736870", "rs113378274", "rs113313554"],
            chrom: ["X", "X", "Y"],
            pos: [304103, 92686057, 624523],
            genotype: ["AA", "AA", "AA"],
        );
    }



    /**
     * Load and assign PAR SNPs.
     * 
     * If downloads are not enabled, use a minimal subset of the real responses.
     * 
     * References:
     * 1. National Center for Biotechnology Information, Variation Services, RefSNP,
     *    https://api.ncbi.nlm.nih.gov/variation/v0/
     * 2. Yates et. al. (doi:10.1093/bioinformatics/btu613),
     *    http://europepmc.org/search/?query=DOI:10.1093/bioinformatics/btu613
     * 3. Zerbino et. al. (doi.org/10.1093/nar/gkx1098), https://doi.org/10.1093/nar/gkx1098
     * 4. Sherry ST, Ward MH, Kholodov M, Baker J, Phan L, Smigielski EM, Sirotkin K.
     *    dbSNP: the NCBI database of genetic variation. Nucleic Acids Res. 2001 Jan 1;
     *    29(1):308-11.
     * 5. Database of Single Nucleotide Polymorphisms (dbSNP). Bethesda (MD): National Center
     *    for Biotechnology Information, National Library of Medicine. dbSNP accession:
     *    rs28736870, rs113313554, rs758419898, and rs113378274 (dbSNP Build ID: 151).
     *    Available from: http://www.ncbi.nlm.nih.gov/SNP/
     * 
     * @param string $path The path to the SNP file
     * 
     * @return SNPs Instance of SNPs class with loaded data
     */
    protected function loadAssignPARSnps(string $path): SNPs
    {
        $effects = [
            [
                "refsnp_id" => "758419898",
                "create_date" => "2015-04-1T22:25Z",
                "last_update_date" => "2019-07-14T04:19Z",
                "last_update_build_id" => "153",
                "primary_snapshot_data" => [
                    "placements_with_allele" => [
                        [
                            "seq_id" => "NC_000024.9",
                            "placement_annot" => [
                                "seq_id_traits_by_assembly" => [
                                    ["assembly_name" => "GRCh37.p13"]
                                ]
                            ],
                            "alleles" => [
                                [
                                    "allele" => [
                                        "spdi" => [
                                            "seq_id" => "NC_000024.9",
                                            "position" => 7364103,
                                        ]
                                    ]
                                ]
                            ],
                        ]
                    ]
                ],
            ],
            [
                "refsnp_id" => "28736870",
                "create_date" => "2005-05-24T14:43Z",
                "last_update_date" => "2019-07-14T04:18Z",
                "last_update_build_id" => "153",
                "primary_snapshot_data" => [
                    "placements_with_allele" => [
                        [
                            "seq_id" => "NC_000023.10",
                            "placement_annot" => [
                                "seq_id_traits_by_assembly" => [
                                    ["assembly_name" => "GRCh37.p13"]
                                ]
                            ],
                            "alleles" => [
                                [
                                    "allele" => [
                                        "spdi" => [
                                            "seq_id" => "NC_000023.10",
                                            "position" => 220769,
                                        ]
                                    ]
                                ]
                            ],
                        ]
                    ]
                ],
            ],
            [
                "refsnp_id" => "113313554",
                "create_date" => "2010-07-4T18:13Z",
                "last_update_date" => "2019-07-14T04:18Z",
                "last_update_build_id" => "153",
                "primary_snapshot_data" => [
                    "placements_with_allele" => [
                        [
                            "seq_id" => "NC_000024.9",
                            "placement_annot" => [
                                "seq_id_traits_by_assembly" => [
                                    ["assembly_name" => "GRCh37.p13"]
                                ]
                            ],
                            "alleles" => [
                                [
                                    "allele" => [
                                        "spdi" => [
                                            "seq_id" => "NC_000024.9",
                                            "position" => 535257,
                                        ]
                                    ]
                                ]
                            ],
                        ]
                    ]
                ],
            ],
            [
                "refsnp_id" => "113378274",
                "create_date" => "2010-07-4T18:14Z",
                "last_update_date" => "2016-03-3T10:51Z",
                "last_update_build_id" => "147",
                "merged_snapshot_data" => ["merged_into" => ["72608386"]],
            ],
            [
                "refsnp_id" => "72608386",
                "create_date" => "2009-02-14T01:08Z",
                "last_update_date" => "2019-07-14T04:05Z",
                "last_update_build_id" => "153",
                "primary_snapshot_data" => [
                    "placements_with_allele" => [
                        [
                            "seq_id" => "NC_000023.10",
                            "placement_annot" => [
                                "seq_id_traits_by_assembly" => [
                                    ["assembly_name" => "GRCh37.p13"]
                                ]
                            ],
                            "alleles" => [
                                [
                                    "allele" => [
                                        "spdi" => [
                                            "seq_id" => "NC_000023.10",
                                            "position" => 91941055,
                                        ]
                                    ]
                                ]
                            ],
                        ]
                    ]
                ],
            ]
        ];

        if ($this->downloads_enabled) {
            return new SNPs($path, assign_par_snps: true, deduplicate_XY_chrom: false);
        } else {
            $mock = $this->createStub(EnsemblRestClient::class);

            $mock->expects($this->exactly(count($effects)))
                ->method('perform_rest_action')
                ->with(self::callback(function ($object): bool {
                    return true;
                }))
                ->willReturn(...$effects);

            $snps = new SNPs(
                $path,
                assign_par_snps: true,
                deduplicate_XY_chrom: false,
                ensemblRestClient: $mock
            );

            return $snps;
        }
    }


    protected function runParsingTestsVcf($file, $source = "vcf", $phased = false, $unannotated = false, $rsids = [], $build = 37, $buildDetected = false, $snpsDf = null)
    {
        // Make assertions using parseFile
        $this->makeParsingAssertionsVcf($this->parseFile($file, $rsids), $source, $phased, $unannotated, $rsids, $build, $buildDetected, $snpsDf);

        // Make assertions using parseBytes
        $this->makeParsingAssertionsVcf($this->parseBytes($file, $rsids), $source, $phased, $unannotated, $rsids, $build, $buildDetected, $snpsDf);

        $tmpdir = sys_get_temp_dir() . '/' . uniqid();
        mkdir($tmpdir);

        $base = basename($file);
        $dest = $tmpdir . '/' . $base . '.gz';
        gzip_file($file, $dest);

        // Make assertions using parseFile with gzipped file
        $this->makeParsingAssertionsVcf($this->parseFile($dest, $rsids), $source, $phased, $unannotated, $rsids, $build, $buildDetected, $snpsDf);

        // Make assertions using parseBytes with gzipped file
        $this->makeParsingAssertionsVcf($this->parseBytes($dest, $rsids), $source, $phased, $unannotated, $rsids, $build, $buildDetected, $snpsDf);

        // Remove .gz extension
        $destWithoutGz = substr($dest, 0, -3);

        rename($dest, $destWithoutGz);

        // Make assertions using parseFile with removed .gz extension
        $this->makeParsingAssertionsVcf($this->parseFile($destWithoutGz, $rsids), $source, $phased, $unannotated, $rsids, $build, $buildDetected, $snpsDf);
    }

    protected function parseBytes($file, $rsids = [])
    {
        $data = file_get_contents($file);
        return new SNPs($data, rsids: $rsids);
    }

    protected function makeParsingAssertionsVcf($snps, $source, $phased, $unannotated, $rsids, $build, $buildDetected, $snpsArray)
    {
        if ($snpsArray === null) {
            $snpsArray = $this->genericSnpsVcfArray();
        }

        $this->assertEquals($snps->getSource(), $source);

        if ($unannotated) {
            $this->assertTrue($snps->isUnannotatedVcf());
            $this->assertEquals(0, $snps->getCount());
        } else {
            $this->assertFalse($snps->isUnannotatedVcf());
            $this->assertEquals($snps->getSnps(), $snpsArray); // Custom array comparison method
        }

        $phased ? $this->assertTrue($snps->isPhased()) : $this->assertFalse($snps->isPhased());
        $this->assertEquals($snps->getBuild(), $build);
        $buildDetected ? $this->assertTrue($snps->isBuildDetected()) : $this->assertFalse($snps->isBuildDetected());
        $this->makeNormalizedArrayAssertions($snps->getSnps());
    }
}
