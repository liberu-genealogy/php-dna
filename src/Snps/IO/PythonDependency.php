<?php

namespace Dna\Snps\IO;

use Exception;
use GuzzleHttp\Client;

class FileHandler
{
    public static function writeFile(string $path, string $content): void
    {
        $file = fopen($path, 'w');
        fwrite($file, $content);
        fclose($file);
    }
}

class DataManipulator
{
    public static function filterArray(array $data, callable $callback): array
    {
        return array_filter($data, $callback, ARRAY_FILTER_USE_BOTH);
    }

    public static function sortArray(array &$data, callable $callback): void
    {
        usort($data, $callback);
    }
}

class ExternalDataFetcher
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function fetchData(string $url): ?array
    {
        try {
            $response = $this->client->request('GET', $url);
            if ($response->getStatusCode() === 200) {
                return json_decode($response->getBody()->getContents(), true);
            }
            return null;
        } catch (Exception $e) {
            error_log("Failed to fetch data from {$url}: " . $e->getMessage());
            return null;
        }
    }
}
