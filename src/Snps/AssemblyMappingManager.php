declare(strict_types=1);

namespace Dna\Snps;

use PharData;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

final class AssemblyMappingManager
{
    public function __construct(
        private readonly Client $httpClient = new Client(),
        private readonly string $resourcePath = __DIR__ . "/resources"
    ) {
        if (!is_dir($this->resourcePath)) {
            mkdir($this->resourcePath, 0755, true);
        }
    }

    /**
     * @throws Exception
     */
    public function getAssemblyMappingData(string $sourceAssembly, string $targetAssembly): string
    {
        $filename = "assembly_mapping_{$sourceAssembly}_to_{$targetAssembly}.tar.gz";
        $filepath = "{$this->resourcePath}/{$filename}";

        if (!file_exists($filepath)) {
            return $this->downloadMappingData($sourceAssembly, $targetAssembly, $filepath);
        }

        return $filepath;
    }

    public static function loadAssemblyMappingData(string $filename): array
    {
        $assemblyMappingData = [];
        try {
            $tar = new PharData($filename);
            foreach ($tar as $file) {
                if (strpos($file->getFilename(), '.json') !== false) {
                    $content = file_get_contents($file->getPathname());
                    $data = json_decode($content, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $assemblyMappingData[] = $data;
                    } else {
                        throw new Exception("Error parsing JSON data.");
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception("Error loading assembly mapping data: " . $e->getMessage());
        }

        return $assemblyMappingData;
    }

    /**
     * @throws GuzzleException
     */
    private function downloadMappingData(string $sourceAssembly, string $targetAssembly, string $filepath): void
    {
        $url = "http://example.com/assembly_mapping/{$sourceAssembly}/{$targetAssembly}";
        $response = $this->httpClient->get($url);
        if ($response->getStatusCode() === 200) {
            file_put_contents($filepath, $response->getBody()->getContents());
        } else {
            throw new GuzzleException("Failed to download assembly mapping data.");
        }
    }
}