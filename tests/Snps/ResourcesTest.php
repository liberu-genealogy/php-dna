<?php declare(strict_types=1);

namespace DnaTest\Snps;

use Dna\Resources;
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
            $this->resource->setResourcesDir("resources");
            return parent::run($result);
        } else {
            // Use a temporary directory for test resource data
            $tmpdir = sys_get_temp_dir();
            $this->resource->setResourcesDir($tmpdir);
            $res = parent::run($result);
            $this->resource->setResourcesDir("resources");
            return $res;
        }
    }

}