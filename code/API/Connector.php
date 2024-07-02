<?php

namespace API;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

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
        $retries = 0;

        do {
            try {
                $response = $client->request($method, $uri, $params);
                $body = json_decode($response->getBody(), true);
                $retries = 0;
            } catch (ClientException $e) {
                var_dump($e->getMessage());
                if (false === ($e->getCode() === 429)) {
                    continue;
                }
                $retries ++;
                $time = $e->getResponse()->getHeader('Retry-After')[0];
                sleep($time);
            }
        } while ($retries > 0);

        return $body;
    }

    protected function connect(string $path, string $method, mixed $params = [], mixed $requestBody = [], string $contentType = 'application/json', $jsonEncodeBody = true): array
    {
        $uri = $this->credentials['url'] . $path;
        $client = new Client(
            [
                'base_uri' => $uri,
                'headers' => [
                    'Authorization' => $this->credentials['apikey'],
                    'Content-Type' => $contentType,
                ],
                'body' => $jsonEncodeBody ? json_encode($requestBody) : $requestBody,
                'params' => json_encode($params),
            ]
        );

        return $this->request($method, $client, $params, $uri);

    }
}