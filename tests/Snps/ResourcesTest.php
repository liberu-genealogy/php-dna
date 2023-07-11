<?php declare(strict_types=1);

namespace DnaTest\Snps;

use Dna\Resources;
use Dna\Snps\SNPs;

class ResourcesTest extends BaseSNPsTestCase
{
    private $resource;
    private $downloads_enabled = false;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function _reset_resource() {
        $this->resource->_init_resource_attributes();
    }

    public function runTest($result = null) {
        // Set resources directory based on if downloads are being performed
        // https://stackoverflow.com/a/11180583

        $this->resource = new Resources();
        $this->_reset_resource();
        if ($this->downloads_enabled) {
            $this->resource->setResourcesDir("resources");
            parent::run($result);
        } else {
            // Use a temporary directory for test resource data
            $tmpdir = sys_get_temp_dir();
            $this->resource->setResourcesDir($tmpdir);
            parent::run($result);
            $this->resource->setResourcesDir("resources");
        }
    }

}