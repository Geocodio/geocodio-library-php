<?php

declare(strict_types=1);

namespace Geocodio\Concerns;

use Geocodio\Exceptions\GeocodioException;
use Geocodio\Geocodio;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Throwable;

trait SendsRequests
{
    /**
     * @throws GeocodioException
     */
    protected function sendRequest(string $method, string $uri, array $options = []): Response
    {
        try {
            return $this->client->request(
                $method,
                $this->formatUrl($uri),
                $this->resolveOptions($options)
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * @throws GeocodioException
     */
    protected function handleException(Throwable $e): void
    {
        if ($e instanceof RequestException && $e->hasResponse()) {

            $response = json_decode((string) $e->getResponse()->getBody(), true);

            throw GeocodioException::requestError(
                $response['error'] ?? 'unknown error',
                $e
            );
        }

        throw new GeocodioException($e->getMessage(), previous: $e);
    }

    protected function resolveOptions(array $options): array
    {
        $options[RequestOptions::QUERY] = array_filter(array_merge(
            [
                'api_key' => $this->apiKey,
            ],
            $options[RequestOptions::QUERY] ?? [],
        ));

        $options[RequestOptions::HEADERS] = array_merge(
            $this->getHeaders(),
            $options[RequestOptions::HEADERS] ?? [],
        );

        return $options;
    }

    protected function formatUrl(string $endpoint): string
    {
        return vsprintf('https://%s/%s/%s', [
            $this->hostname,
            $this->apiVersion,
            $endpoint,
        ]);
    }

    protected function formattedReverseQuery($query)
    {
        if (is_array($query) && count($query) === 2) {
            [$latitude, $longitude] = $query;

            if (is_numeric($latitude) && is_numeric($longitude)) {
                return $latitude.','.$longitude;
            }
        }

        return $query;
    }

    protected function getHeaders(): array
    {
        return [
            'User-Agent' => sprintf('geocodio-library-php/%s', Geocodio::SDK_VERSION),
            'Accept' => 'application/json',
        ];
    }
}
