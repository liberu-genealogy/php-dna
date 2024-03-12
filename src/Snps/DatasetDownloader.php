<?php

namespace Dna\Snps;

use GuzzleHttp\Client;
use ZipArchive;
use PharData;
use League\Csv\Reader;
use League\Csv\Statement;

class DatasetDownloader
{
    private Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client();
    }

    public function download_example_datasets(): array
    {
        $paths = [];
        $paths[] = $this->download_file("https://opensnp.org/data/662.23andme.340", "662.23andme.340.txt.gz", true);
        $paths[] = $this->download_file("https://opensnp.org/data/662.ftdna-illumina.341", "662.ftdna-illumina.341.csv.gz", true);
        return $paths;
    }

    public function getAllResources()
    {
        $resources = [];
        $resources["gsa_resources"] = $this->getGsaResources();
        $resources["chip_clusters"] = $this->get_chip_clusters();
        $resources["low_quality_snps"] = $this->getLowQualitySNPs();
        return $resources;
    }

    public function getGsaResources(): array
    {
        // Implementation similar to SNPsResources::getGsaResources
    }

    public function get_chip_clusters()
    {
        // Implementation similar to SNPsResources::get_chip_clusters
    }

    public function getLowQualitySNPs(): array
    {
        // Implementation similar to SNPsResources::getLowQualitySNPs
    }

    public function get_dbsnp_151_37_reverse(): ?array
    {
        // Implementation similar to SNPsResources::get_dbsnp_151_37_reverse
    }

    public function getOpensnpDatadumpFilenames(): array
    {
        // Implementation similar to SNPsResources::getOpensnpDatadumpFilenames
    }

    private function download_file(string $url, string $filename, bool $compress = false): string
    {
        // Implementation similar to SNPsResources::download_file
    }
}
