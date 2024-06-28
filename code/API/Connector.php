<?php

namespace API;

use GuzzleHttp\Client;
use Throwable;

abstract class Connector
{
    private array $credentials;

    function __construct($credentials = [])
    {
        $this->credentials = $credentials;
    }

    private function request($method, $client): array
    {
        $body = [];

        try {
            $response = $client->request($method);
            $body = json_decode($response->getBody(), true);
        } catch (Throwable $e) {
            var_dump($e->getMessage());
        }

        return $body;
    }

    protected function connect(string $path, string $method): array
    {
        $client = new Client(
            [
                'base_uri' => sprintf('%s/%s', $this->credentials['url'], $path),
                'headers' => [
                    'Authorization' => base64_encode(sprintf('%s', $this->credentials['apikey'])),
                ]
            ]
        );

        return $this->request($method, $client);

    }
}