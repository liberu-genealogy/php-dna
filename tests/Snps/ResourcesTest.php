<?php

declare(strict_types=1);

namespace DnaTest\Snps;

use Dna\Resources;
use Dna\Snps\EnsemblRestClient;
use Dna\Snps\SNPs;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestResult;

class ResourcesTest extends BaseSNPsTestCase
{
    private Resources $resource;
    private $downloads_enabled = false;

    private function _reset_resource()
    {
        $this->resource->init_resource_attributes();
    }

    public function setUp($result = null): void
    {
        // Set resources directory based on if downloads are being performed
        // https://stackoverflow.com/a/11180583
        $this->resource = new Resources();
        $this->_reset_resource();
        if ($this->downloads_enabled) {
            $this->resource->setResourcesDir("./resources");
        } else {
            // Use a temporary directory for test resource data
            $tmpdir = sys_get_temp_dir();
            $this->resource->setResourcesDir($tmpdir);
        }
        parent::setUp($result);
    }

    public function tearDown(): void
    {
        $this->_reset_resource();
        unset($this->resource);
    }

    public function testGetAssemblyMappingData(): void
    {
        $f = function () {
            $effects = array_fill(0, 25, ["mappings" => []]);
            foreach ($this->NCBI36_GRCh37() as $k => $v) {
                $effects[intval($k) - 1] = $v;
            }
            $mock = $this->getMockBuilder(EnsemblRestClient::class)
                ->disableOriginalConstructor()
                ->getMock();
            $mock->expects($this->any())
                ->method("perform_rest_action")
                ->will($this->onConsecutiveCalls(...$effects));

            $this->resource->setRestClient($mock);
            return $this->resource->getAssemblyMappingData("NCBI36", "GRCh37");
        };

        $assembly_mapping_data = ($this->downloads_enabled) ?
            $this->resource->getAssemblyMappingData("NCBI36", "GRCh37") :
            $f();

        $this->assertCount(25, $assembly_mapping_data);
    }

    public function testGetGsaResources(): void
    {
        $f = function () {
            // mock download of test data for each resource
            $this->_generateTestGsaResources();
            // load test resources saved to `tmpdir`
            return $this->resource->getGsaResources();
        };

        $gsa_resources = ($this->downloads_enabled) ?
            $this->resource->getGsaResources() :
            $f();

        $this->assertCount(618540, $gsa_resources["rsid_map"]);
        $this->assertCount(665608, $gsa_resources["chrpos_map"]);
        $this->assertCount(2393418, $gsa_resources["dbsnp_151_37_reverse"]);
    }

    protected function _generateTestGsaResources(): void
    {
        $s = "Name\tRsID\n";
        for ($i = 1; $i <= 618540; $i++) {
            $s .= "rs{$i}\trs{$i}\n";
        }

        $mockResponse = new Response(200, [], $s);
        $httpClient = $this->createMockHttpClient([$mockResponse], true);
        $this->resource->setHttpClient($httpClient);
        $this->resource->getGsaRsid();

        $s = "Name\tChr\tMapInfo\tdeCODE(cM)\n";
        for ($i = 1; $i <= 665608; $i++) {
            $s .= "rs{$i}\t1\t{$i}\t0.0000\n";
        }

        $mockResponse = new Response(200, [], $s);
        $httpClient = $this->createMockHttpClient([$mockResponse], true);
        $this->resource->setHttpClient($httpClient);
        $this->resource->getGsaChrpos();

        $s = "# comment\n";
        $s .= "rs1 0.0 0.0 0.0 0.0\n";
        for ($i = 2; $i <= 2393418; $i++) {
            $s .= "rs{$i}\n";
        }

        $mockResponse = new Response(200, [], $s);
        $httpClient = $this->createMockHttpClient([$mockResponse], true);
        $this->resource->setHttpClient($httpClient);
        $this->resource->get_dbsnp_151_37_reverse();
    }


    public function testGetAllResources()
    {
        $f = function () {
            // mock download of test data for each resource
            $this->_generateTestGsaResources();
            $this->_generate_test_chip_clusters();
            $this->_generate_test_low_quality_snps();

            // generate test data for permutations of remapping data
            $effects = array_fill(0, 25, array("mappings" => array()));
            foreach ($this->NCBI36_GRCh37() as $k => $v) {
                $effects[(int)$k - 1] = $v;
            }

            $mock = $this->getMockBuilder(EnsemblRestClient::class)
                ->getMock();
            $mock->method("perform_rest_action")
                ->willReturnOnConsecutiveCalls(...array_fill(0, 6, $effects));

            $this->resource->setRestClient($mock);
            return $this->resource->getAllResources();
        };

        $resources = $this->downloads_enabled ? $this->resource->getAllResources() : $f();
        foreach ($resources as $k => $v) {
            $this->assertGreaterThanOrEqual(0, count($v));
        }
    }

    protected function _generate_test_chip_clusters()
    {
        $responseContent = "1:1\tc1\n" . str_repeat("1:1\tc1\n", 2135213);

        $mockResponse = new Response(200, [], $responseContent);
        $httpClient = $this->createMockHttpClient([$mockResponse], true);
        $this->resource->setHttpClient($httpClient);

        return $this->resource->get_chip_clusters();
    }

    public function testGetChipClusters()
    {
        $chip_clusters = $this->downloads_enabled
            ? $this->resource->get_chip_clusters()
            : $this->_generate_test_chip_clusters();

        $this->assertEquals(2135214, count($chip_clusters));
    }

    protected function _generate_test_low_quality_snps()
    {
        $mockResponseContent = "c1\t" . str_repeat("1:1,", 56024) . "1:1\n";

        $mockResponse = new Response(200, ['Content-Encoding' => 'gzip'], gzencode($mockResponseContent));
        $httpClient = $this->createMockHttpClient([$mockResponse]);
        $this->resource->setHttpClient($httpClient);

        return $this->resource->getLowQualitySNPs();
    }

    public function testGetLowQualitySNPs()
    {
        $f = function () {
            return $this->_generate_test_low_quality_snps();
            // return $this->resource->getLowQualitySNPs();
        };

        $lowQualitySnps = ($this->downloads_enabled) ? $this->resource->getLowQualitySNPs() : $f();

        $this->assertEquals(56025, count($lowQualitySnps));
    }

    // make public to use in test
    protected function testDownloadExampleDatasets()
    {
        $f = function () {
            $mockResponse = new Response(200, [], '');
            $httpClient = $this->createMockHttpClient([$mockResponse]);
            $this->resource->setHttpClient($httpClient);
            return $this->resource->download_example_datasets();
        };

        $paths = ($this->downloads_enabled) ? $this->resource->download_example_datasets() : $f();

        foreach ($paths as $path) {
            if (empty($path) || !file_exists($path)) {
                echo "Example dataset(s) not currently available\n";
                return;
            }
        }
    }

    public function testGetPathsReferenceSequencesInvalidAssembly()
    {
        [$assembly, $chroms, $urls, $paths] = $this->resource->getPathsReferenceSequences(
            assembly: "36"
        );

        $chroms = $chroms;
        $urls = $urls;
        $paths = $paths;

        $this->assertEmpty($assembly);
        $this->assertEmpty($chroms);
        $this->assertEmpty($urls);
        $this->assertEmpty($paths);
    }

    protected function runReferenceSequencesTest(callable $f, string $assembly = "GRCh37")
    {
        if ($this->downloads_enabled) {
            $f();
        } else {
            $s = ">MT dna:chromosome chromosome:{$assembly}:MT:1:16569:1 REF\n";
            for ($i = 0; $i < 276; $i++) {
                $s .= str_repeat("A", 60);
                $s .= "\n";
            }
            $s .= str_repeat("A", 9);
            $s .= "\n";

            $mockResponse = new Response(200, ['Content-Encoding' => 'gzip'], gzcompress($s));
            $httpClient = $this->createMockHttpClient([$mockResponse]);
            $this->resource->setHttpClient($httpClient);

            $f();
        }
    }

    protected function runCreateReferenceSequencesTest(string $assemblyExpect, string $urlExpect)
    {
        $f = function () use ($assemblyExpect, $urlExpect) {
            [$assembly, $chroms, $urls, $paths] = $this->resource->getPathsReferenceSequences(
                assembly: $assemblyExpect, 
                chroms: ["MT"]
            );
            $seqs = $this->resource->create_reference_sequences($assembly, $chroms, $urls, $paths);

            $this->assertCount(1, $seqs);
            $this->assertEquals($seqs["MT"]->__toString(), "ReferenceSequence(assembly='{$assemblyExpect}', ID='MT')");
            $this->assertEquals($seqs["MT"]->ID, "MT");
            $this->assertEquals($seqs["MT"]->chrom, "MT");
            $this->assertEquals($seqs["MT"]->url, $urlExpect);
            $this->assertEquals($seqs["MT"]->path, $this->resource->relativePathToFasta($assemblyExpect, basename($urlExpect)));
            $this->assertTrue(file_exists($seqs["MT"]->path));
            $this->assertEquals($seqs["MT"]->assembly, $assemblyExpect);
            $this->assertEquals($seqs["MT"]->build, "B" . substr($assemblyExpect, -2));
            $this->assertEquals($seqs["MT"]->species, "Homo sapiens");
            $this->assertEquals($seqs["MT"]->taxonomy, "x");
        };

        $this->runReferenceSequencesTest($f, $assemblyExpect);
    }

    public function testCreateReferenceSequencesNCBI36()
    {
        $this->runCreateReferenceSequencesTest(
            "NCBI36",
            "ftp://ftp.ensembl.org/pub/release-54/fasta/homo_sapiens/dna/Homo_sapiens.NCBI36.54.dna.chromosome.MT.fa.gz"
        );
    }
}
