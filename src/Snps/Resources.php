<?php

namespace Dna\Snps;

use GuzzleHttp\Client;

class Resources
{
    private string $baseUrl;
    private string $localResourceDir;
    private Client $httpClient;

    public function __construct(string $baseUrl, string $localResourceDir)
    {
        $this->baseUrl = $baseUrl;
        $this->localResourceDir = $localResourceDir;
        $this->httpClient = new Client();
    }

    public function downloadResource(string $url, string $destinationPath): void
    {
        $response = $this->httpClient->get($url);
        file_put_contents($destinationPath, $response->getBody());
    }

    public function loadDataFromFile(string $filePath)
    {
        return file_get_contents($filePath);
    }

    public function getReferenceSequence(string $id): ReferenceSequence
    {
        $filePath = $this->getLocalPathForResource($id);
        $sequenceData = $this->loadDataFromFile($filePath);
        return new ReferenceSequence($id, $sequenceData);
    }

    public function getAssemblyMappingData(string $id)
    {
        // Implementation for fetching assembly mapping data
    }

    public function getExampleDataset(string $id)
    {
        // Implementation for fetching example datasets
    }

    private function getLocalPathForResource(string $resourceId): string
    {
        return $this->localResourceDir . DIRECTORY_SEPARATOR . $resourceId;
    }
}
