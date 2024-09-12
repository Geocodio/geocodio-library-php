<?php

namespace Geocodio;

use Exception;
use Geocodio\Exceptions\GeocodioException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

class Geocodio
{
    /**
     * @var string Geocodio API Key
     *
     * @see https://dash.geocod.io/apikey
     */
    private ?string $apiKey = null;

    /**
     * @var string API Hostname, change this if using Geocodio+HIPAA or on-premise
     */
    private string $hostname = 'api.geocod.io';

    /**
     * @var string API Version to use, defaults to most recent
     *
     * @see https://www.geocod.io/docs/#changelog
     */
    private string $apiVersion = 'v1.7';

    const ADDRESS_COMPONENT_PARAMETERS = [
        'street',
        'city',
        'state',
        'postal_code',
        'country',
    ];

    public function __construct(private readonly Client $client = new Client)
    {
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function setHostname(string $hostname): self
    {
        $this->hostname = $hostname;

        return $this;
    }

    public function setApiVersion(string $apiVersion): self
    {
        $this->apiVersion = $apiVersion;

        return $this;
    }

    /**
     * Forward geocode an address or a list of addresses
     *
     * @param  string|array  $query
     * @return array|object
     */
    public function geocode($query, array $fields = [], ?int $limit = null, ?string $format = null)
    {
        return $this->handleRequest('geocode', $query, $fields, $limit, $format);
    }

    /**
     * Reverse geocode a coordinate or a list of coordinates
     *
     * @param  string|array  $query
     * @return array|object
     */
    public function reverse($query, array $fields = [], ?int $limit = null, ?string $format = null)
    {
        return $this->handleRequest('reverse', $query, $fields, $limit, $format);
    }

    private function handleRequest(string $endpoint, $query, array $fields = [], ?int $limit = null, ?string $format = null)
    {
        $url = $this->formatUrl($endpoint);

        $queryParameters = array_filter([
            'api_key' => $this->apiKey,
            'fields' => implode(',', $fields),
            'limit' => $limit,
            'format' => $format,
        ]);

        $query = $this->preprocessQuery($query, $endpoint);

        try {
            if ($this->isSingleQuery($query)) {
                $response = $this->performSingleRequest($url, $query, $queryParameters);
            } else {
                $query = array_map(fn($item) => $this->preprocessQuery($item, $endpoint), $query);

                $response = $this->performBatchRequest($url, $query, $queryParameters);
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }

        return $this->formatResponse($response);
    }

    private function formatUrl(string $endpoint): string
    {
        return sprintf('https://%s/%s/%s', $this->hostname, $this->apiVersion, $endpoint);
    }

    private function preprocessQuery($query, string $endpoint)
    {
        // Convert lat/lon array to a comma-separated string
        if ($endpoint === 'reverse' && is_array($query) && count($query) === 2) {
            [$latitude, $longitude] = $query;

            if (is_numeric($latitude) && is_numeric($longitude)) {
                return $latitude.','.$longitude;
            }
        }

        return $query;
    }

    private function isSingleQuery($query): bool
    {
        if (is_array($query)) {
            $addressComponentKeys = array_intersect(array_keys($query), self::ADDRESS_COMPONENT_PARAMETERS);

            return count($addressComponentKeys) >= 1;
        }

        return true;
    }

    private function performSingleRequest(string $url, $query, array $queryParameters): \Psr\Http\Message\ResponseInterface
    {
        if (is_array($query)) {
            $queryParameters += $query;
        } else {
            $queryParameters['q'] = $query;
        }

        return $this->client->get($url, [
            'query' => $queryParameters,
            'headers' => $this->getHeaders(),
        ]);
    }

    private function performBatchRequest(string $url, array $queries, array $queryParameters): \Psr\Http\Message\ResponseInterface
    {
        return $this->client->post($url, [
            'query' => $queryParameters,
            'json' => $queries,
            'headers' => $this->getHeaders(),
        ]);
    }

    private function handleException(\Throwable $e): void
    {
        $response = $e instanceof RequestException && $e->hasResponse() ? $e->getResponse() : null;

        $errorMessage = 'Error';
        $errorCode = 0;

        if ($response instanceof \Psr\Http\Message\ResponseInterface) {
            $json = @json_decode((string) $response->getBody());

            if ($json && isset($json->error)) {
                $errorMessage = $json->error;
            }

            $errorCode = $response->getStatusCode();
        }

        throw new GeocodioException($errorMessage, $errorCode, $e);
    }

    private function formatResponse(ResponseInterface $response): mixed
    {
        return json_decode((string) $response->getBody());
    }

    private function getHeaders(): array
    {
        return [
            'User-Agent' => 'geocodio-library-php/1.2.0',
        ];
    }
}
