<?php

namespace Dna\Snps;

use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class Ensembl
{
    private string $server;
    private int $reqs_per_sec;
    private int $req_count;
    private float $last_req;

    public function __construct(string $server = "https://rest.ensembl.org", int $reqs_per_sec = 15)
    {
        $this->server = $server;
        $this->reqs_per_sec = $reqs_per_sec;
        $this->req_count = 0;
        $this->last_req = microtime(true);
    }

    public function performRestAction(string $endpoint, ?array $hdrs = null, ?array $params = null): ?array
    {
        if ($hdrs === null) {
            $hdrs = [];
        }

        if (!array_key_exists("Content-Type", $hdrs)) {
            $hdrs["Content-Type"] = "application/json";
        }

        if ($params) {
            $endpoint .= "?" . http_build_query($params);
        }

        $this->rateLimit();

        $client = HttpClient::create();
        $url = $this->server . $endpoint;
        $options = [
            'headers' => $hdrs,
            'query' => $params,
        ];

        try {
            $response = $client->request('GET', $url, $options);
            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                return $response->toArray();
            } elseif ($statusCode == 429) {
                $retryAfter = $response->getHeaders()['retry-after'][0] ?? 1;
                sleep($retryAfter);
                return $this->performRestAction($endpoint, $hdrs, $params);
            } else {
                throw new Exception("HTTP request failed with status code {$statusCode}.");
            }
        } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e) {
            error_log("Request failed for {$endpoint}: " . $e->getMessage());
            return null;
        }
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
