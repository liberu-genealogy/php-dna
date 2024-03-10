<?php

namespace Dna\Snps;

use Exception;

class EnsemblRestClient
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
        $this->last_req = 0;
    }

    public function perform_rest_action(string $endpoint, ?array $hdrs = null, ?array $params = null): ?array
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

        $data = null;

        // check if we need to rate limit ourselves
        if ($this->req_count >= $this->reqs_per_sec) {
            $delta = microtime(true) - $this->last_req;
            if ($delta < 1) {
                usleep((1 - $delta) * 1000000);
            }

            $this->last_req = microtime(true);
            $this->req_count = 0;
        }

        $client = new \Symfony\Component\HttpClient\HttpClient();
        $url = $this->server . $endpoint;
        $options = [
            'headers' => $hdrs,
            'query' => $params,
        ];

        try {
            $response = $client->request('GET', $url, $options);
            $statusCode = $response->getStatusCode();

            if ($statusCode === 200) {
                $data = $response->toArray();
            } else {
                throw new Exception("HTTP request failed with status code {$statusCode}.");
            }

            $this->req_count++;
        } catch (Exception $e) {
            if ($statusCode == 429) {
                $retryAfter = $response->getHeaders()['retry-after'][0] ?? 0;
                sleep($retryAfter);
                return $this->perform_rest_action($endpoint, $hdrs, $params);
            } else {
                error_log("Request failed for {$endpoint}: Status code: {$statusCode} Reason: {$e->getMessage()}\n");
            }
        }

        return $data;
    }
}

?>