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

    private function request($method, $client, $params, $uri): array
    {
        $body = [];


        try {
            $response = $client->request($method, $uri, $params);
            $body = json_decode($response->getBody(), true);
        } catch (Throwable $e) {
            var_dump($e->getMessage());
        }

        return $body;
    }

    protected function connect(string $path, string $method, array $params = [], array $requestBody = []): array
    {
        $uri = sprintf('%s%s', $this->credentials['url'], $path);
        $client = new Client(
            [
                'base_uri' => sprintf('%s%s', $this->credentials['url'], $path),
                'headers' => [
                    'Authorization' => sprintf('%s', $this->credentials['apikey']),
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($requestBody),
                'params' => json_encode($params),
            ]
        );

        return $this->request($method, $client, $params, $uri);

    }
}