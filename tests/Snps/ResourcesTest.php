<?php declare(strict_types=1);

namespace DnaTest\Snps;

use Dna\Resources;
use Dna\Snps\EnsemblRestClient;
use Dna\Snps\SNPs;
use PHPUnit\Framework\TestResult;

class ResourcesTest extends BaseSNPsTestCase
{
    private $resource;
    private $downloads_enabled = false;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function _reset_resource() {
        $this->resource->init_resource_attributes();
    }

    public function run($result = null) : TestResult
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
            $this->resource->setResourcesDir(__DIR__."resources");
            return $res;
        }
    }

    // def test_get_assembly_mapping_data(self):
    //     def f():
    //         effects = [{"mappings": []} for _ in range(1, 26)]
    //         for k, v in self.NCBI36_GRCh37().items():
    //             effects[int(k) - 1] = v
    //         mock = Mock(side_effect=effects)
    //         with patch("snps.ensembl.EnsemblRestClient.perform_rest_action", mock):
    //             return self.resource.get_assembly_mapping_data("NCBI36", "GRCh37")

    //     assembly_mapping_data = (
    //         self.resource.get_assembly_mapping_data("NCBI36", "GRCh37")
    //         if self.downloads_enabled
    //         else f()
    //     )

    //     self.assertEqual(len(assembly_mapping_data), 25)

    public function testGetAssemblyMappingData(): void 
    {
        $f = function() {
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
    
}
