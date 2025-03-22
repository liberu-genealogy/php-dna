declare(strict_types=1);

namespace Dna\Snps;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use League\Csv\Reader;

final class DatasetDownloader
{
    public function __construct(
        private readonly Client $httpClient = new Client(),
        private readonly string $cacheDir = __DIR__ . '/../../cache'
    ) {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * @return array<string>
     * @throws GuzzleException
     */
    public function downloadExampleDatasets(): array
    {
        return [
            $this->downloadFile("https://opensnp.org/data/662.23andme.340", "662.23andme.340.txt.gz"),
            $this->downloadFile("https://opensnp.org/data/662.ftdna-illumina.341", "662.ftdna-illumina.341.csv.gz")
        ];
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
