<?php

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

        $context_options = [
            "http" => [
                "method" => "GET",
                "header" => $hdrs,
            ],
        ];

        $context = stream_context_create($context_options);
        $url = $this->server . $endpoint;

        try {
            $response = file_get_contents($url, false, $context);
            $statusCode = http_response_code();

            if ($response) {
                $data = json_decode($response, true);
            }

            $this->req_count++;

        } catch (Exception $e) {
            // check if we are being rate limited by the server
            if ($statusCode == 429) {
                $retryAfterHeader = '';
                foreach ($http_response_header as $header) {
                    if (str_starts_with($header, 'Retry-After')) {
                        $retryAfterHeader = $header;
                        break;
                    }
                }

                if ($retryAfterHeader !== '') {
                    $retry = substr($retryAfterHeader, strlen('Retry-After: '));
                    sleep($retry);
                    
                    return $this->perform_rest_action($endpoint, $hdrs, $params);
                }
            } else {
                error_log("Request failed for {$endpoint}: Status code: {$statusCode} Reason: {$e->getMessage()}\n");
            }
        }

        return $data;
    }
}

?>