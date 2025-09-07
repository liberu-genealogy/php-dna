<?php

declare(strict_types=1);

namespace Dna\Snps;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * REST client for accessing Ensembl and NCBI APIs
 */
class EnsemblRestClient
{
    private Client $client;
    private string $server;
    private float $reqsPerSec;
    private float $lastRequestTime = 0;

    public function __construct(
        string $server = 'https://api.ncbi.nlm.nih.gov',
        float $reqsPerSec = 1.0
    ) {
        $this->server = $server;
        $this->reqsPerSec = $reqsPerSec;
        $this->client = new Client([
            'base_uri' => $server,
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }

    /**
     * Perform a REST API action with rate limiting
     *
     * @param string $endpoint The API endpoint to call
     * @param array $params Query parameters
     * @return array|null The decoded JSON response or null on error
     */
    public function perform_rest_action(string $endpoint, array $params = []): ?array
    {
        $this->rateLimit();

        try {
            $response = $this->client->get($endpoint, [
                'query' => $params
            ]);

            if ($response->getStatusCode() === 200) {
                $body = $response->getBody()->getContents();
                return json_decode($body, true);
            }
        } catch (GuzzleException $e) {
            error_log("REST API error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Rate limiting to respect API limits
     */
    private function rateLimit(): void
    {
        $currentTime = microtime(true);
        $timeSinceLastRequest = $currentTime - $this->lastRequestTime;
        $minInterval = 1.0 / $this->reqsPerSec;

        if ($timeSinceLastRequest < $minInterval) {
            $sleepTime = $minInterval - $timeSinceLastRequest;
            usleep((int)($sleepTime * 1000000));
        }

        $this->lastRequestTime = microtime(true);
    }

    /**
     * Get assembly mapping data from Ensembl
     *
     * @param string $species Species name (e.g., 'human')
     * @param string $fromAssembly Source assembly
     * @param string $toAssembly Target assembly
     * @param string $region Genomic region
     * @return array|null Mapping data or null on error
     */
    public function getAssemblyMapping(
        string $species,
        string $fromAssembly,
        string $toAssembly,
        string $region
    ): ?array {
        $endpoint = "/map/{$species}/{$fromAssembly}/{$region}/{$toAssembly}";
        return $this->perform_rest_action($endpoint);
    }

    /**
     * Lookup RefSNP snapshot from NCBI
     *
     * @param string $rsid The rs ID (without 'rs' prefix)
     * @return array|null RefSNP data or null on error
     */
    public function lookupRefsnpSnapshot(string $rsid): ?array
    {
        $id = str_replace("rs", "", $rsid);
        return $this->perform_rest_action("/variation/v0/refsnp/" . $id);
    }
}