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

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function _reset_resource()
    {
        $this->resource->init_resource_attributes();
    }

    public function run($result = null): TestResult
    {
        // Set resources directory based on if downloads are being performed
        // https://stackoverflow.com/a/11180583

        $this->resource = new Resources();
        $this->_reset_resource();
        if ($this->downloads_enabled) {
            $this->resource->setResourcesDir("./resources");
            return parent::run($result);
        } else {
            // Use a temporary directory for test resource data
            $tmpdir = sys_get_temp_dir();
            $this->resource->setResourcesDir($tmpdir);
            $res = parent::run($result);
            $this->resource->setResourcesDir(__DIR__ . "resources");
            return $res;
        }
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


    // function testGetAllResources()
    // {
    //     $f = function () {
    //         // mock download of test data for each resource
    //         $this->_generateTestGsaResources();
    //         $this->_generate_test_chip_clusters();
    //         $this->_generate_test_low_quality_snps();

    //         // generate test data for permutations of remapping data
    //         $effects = array_fill(0, 25, array("mappings" => array()));
    //         foreach ($this->NCBI36_GRCh37() as $k => $v) {
    //             $effects[(int)$k - 1] = $v;
    //         }

    //         $mock = $this->getMockBuilder(EnsemblRestClient::class)
    //             ->getMock();
    //         $mock->method("perform_rest_action")
    //             ->willReturnOnConsecutiveCalls(...array_fill(0, 6, $effects));

    //         return $this->resource->getAllResources();
    //     };

    //     $resources = $this->downloads_enabled ? $this->resource->getAllResources() : $f();

    //     foreach ($resources as $k => $v) {
    //         $this->assertGreaterThanOrEqual(0, count($v));
    //     }
    // }



    protected function _generate_test_chip_clusters(): void
    {
        $responseContent = "1:1\tc1\n" . str_repeat("1:1\tc1\n", 2135213);

        $mockResponse = new Response(200, [], $responseContent);
        $httpClient = $this->createMockHttpClient([$mockResponse], true);
        $this->resource->setHttpClient($httpClient);

        $this->resource->get_chip_clusters();
    }

    public function test_get_chip_clusters()
    {
        $f = function () {
            // mock download of test data for chip clusters
            $this->_generate_test_chip_clusters();
            // load test resource
            return $this->resource->get_chip_clusters();
        };

        $chip_clusters = $this->downloads_enabled ? $this->resource->get_chip_clusters() : $f();

        $this->assertEquals(2135214, count($chip_clusters));
    }

    protected function _generate_test_low_quality_snps()
    {
        $mockResponseContent = "c1\t" . str_repeat("1:1,", 56024) . "1:1\n";

        $mockResponse = new Response(200, ['Content-Encoding' => 'gzip'], gzcompress($mockResponseContent));
        $httpClient = $this->createMockHttpClient([$mockResponse]);
        $this->resource->setHttpClient($httpClient);

        $this->resource->getLowQualitySNPs();
    }

    public function testGetLowQualitySNPs()
    {
        $f = function () {
            $this->_generate_test_low_quality_snps();
            return $this->resource->getLowQualitySNPs();
        };

        $lowQualitySnps = ($this->downloads_enabled) ? $this->resource->getLowQualitySNPs() : $f();

        $this->assertEquals(56025, count($lowQualitySnps));
    }
}
