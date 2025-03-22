<?php

declare(strict_types=1);

namespace Dna\Snps;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class Ensembl
{
    private const DEFAULT_SERVER = "https://rest.ensembl.org";
    private const DEFAULT_REQS_PER_SEC = 15;

    public function __construct(
        private readonly string $server = self::DEFAULT_SERVER,
        private readonly int $reqs_per_sec = self::DEFAULT_REQS_PER_SEC,
        private int $req_count = 0,
        private float $last_req = 0.0
    ) {
        $this->last_req = microtime(true);
    }

    public function performRestAction(
        string $endpoint,
        array $headers = [],
        array $params = []
    ): ?array {
        $headers['Content-Type'] ??= 'application/json';
        
        $this->rateLimit();

        try {
            $response = $this->makeRequest($endpoint, $headers, $params);
            return $this->handleResponse($response);
        } catch (TransportExceptionInterface $e) {
            error_log("Request failed for {$endpoint}: " . $e->getMessage());
            return null;
        }
    }

    private function makeRequest(string $endpoint, array $headers, array $params): ResponseInterface 
    {
        $client = HttpClient::create();
        return $client->request('GET', "{$this->server}{$endpoint}", [
            'headers' => $headers,
            'query' => $params,
        ]);
    }

    private function rateLimit(): void
    {
        if ($this->req_count >= $this->reqs_per_sec) {
            $delta = microtime(true) - $this->last_req;
            if ($delta < 1) {
                usleep((1 - $delta) * 1000000);
            }
            $this->last_req = microtime(true);
            $this->req_count = 0;
        } else {
            $this->req_count++;
        }
    }
}
?>
