<?php

declare(strict_types=1);

namespace DnaTest\Snps;

use Dna\Resources;
use Dna\Snps\EnsemblRestClient;
use Dna\Snps\SNPs;
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
            $this->resource->get_assembly_mapping_data("NCBI36", "GRCh37") :
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
        $mockRsid = $this->getMockBuilder('stdClass')
            ->addMethods(['read'])
            ->getMock();
        $mockRsid->expects($this->any())
            ->method('read')
            ->willReturn(gzcompress($s));
        $this->resource->getGsaRsid();

        $s = "Name\tChr\tMapInfo\tdeCODE(cM)\n";
        for ($i = 1; $i <= 665608; $i++) {
            $s .= "rs{$i}\t1\t{$i}\t0.0000\n";
        }
        $mockChrpos = $this->getMockBuilder('stdClass')
            ->addMethods(['read'])
            ->getMock();
        $mockChrpos->expects($this->any())
            ->method('read')
            ->willReturn(gzcompress($s));
        $this->resource->getGsaChrpos();

        $s = "# comment\n";
        $s .= "rs1 0.0 0.0 0.0 0.0\n";
        for ($i = 2; $i <= 2393418; $i++) {
            $s .= "rs{$i}\n";
        }
        $mockDbsnp = $this->getMockBuilder('stdClass')
            ->addMethods(['read'])
            ->getMock();
        $mockDbsnp->expects($this->any())
            ->method('read')
            ->willReturn(gzcompress($s));
        $this->resource->get_dbsnp_151_37_reverse();
    }
}
