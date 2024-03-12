&lt;?php

namespace Dna\Snps;

use PharData;
use GuzzleHttp\Client;
use Exception;

class AssemblyMappingManager
{
    private Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client();
    }

    public function getAssemblyMappingData(string $sourceAssembly, string $targetAssembly): string
    {
        $filename = "assembly_mapping_{$sourceAssembly}_to_{$targetAssembly}.tar.gz";
        $filepath = __DIR__ . "/resources/{$filename}";

        if (!file_exists($filepath)) {
            $url = "http://example.com/assembly_mapping/{$sourceAssembly}/{$targetAssembly}";
            try {
                $response = $this->httpClient->get($url);
                if ($response->getStatusCode() === 200) {
                    file_put_contents($filepath, $response->getBody()->getContents());
                } else {
                    throw new Exception("Failed to download assembly mapping data.");
                }
            } catch (Exception $e) {
                throw new Exception("Error downloading assembly mapping data: " . $e->getMessage());
            }
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
}
