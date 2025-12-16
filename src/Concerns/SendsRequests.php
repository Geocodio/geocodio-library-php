<?php

declare(strict_types=1);

namespace Geocodio\Concerns;

use Geocodio\Data\Coordinate;
use Geocodio\Enums\DistanceMode;
use Geocodio\Enums\DistanceOrderBy;
use Geocodio\Enums\DistanceSortOrder;
use Geocodio\Enums\DistanceUnits;
use Geocodio\Exceptions\GeocodioException;
use Geocodio\Geocodio;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Throwable;

trait SendsRequests
{
    /**
     * @throws GeocodioException
     */
    protected function sendRequest(string $method, string $uri, array $options = [], ?int $timeoutMs = null): ResponseInterface
    {
        try {
            return $this->client->request(
                $method,
                $this->formatUrl($uri),
                $this->resolveOptions($options, $timeoutMs)
            );
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * @throws GeocodioException
     */
    protected function handleException(Throwable $e): never
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

    protected function resolveOptions(array $options, ?int $timeoutMs = null): array
    {
        $options[RequestOptions::HEADERS] = array_merge(
            $this->getHeaders(),
            $options[RequestOptions::HEADERS] ?? [],
        );

        if ($timeoutMs !== null) {
            $options[RequestOptions::TIMEOUT] = $timeoutMs / 1000; // Convert ms to seconds for Guzzle
        }

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

    /**
     * Format coordinate as string for GET requests.
     */
    protected function formatCoordinateAsString(Coordinate|string|array $coordinate): string
    {
        if ($coordinate instanceof Coordinate) {
            return $coordinate->toString();
        }

        if (is_string($coordinate)) {
            // Validate it's a coordinate format, not an address
            if ($this->isCoordinateString($coordinate)) {
                return $coordinate;
            }

            // Return as-is for addresses
            return $coordinate;
        }

        // Array format: [lat, lng] or [lat, lng, id]
        return Coordinate::fromArray($coordinate)->toString();
    }

    /**
     * Format coordinate as object for POST requests.
     *
     * Returns either:
     * - array{lat: float, lng: float, id?: string} for coordinates
     * - string for addresses (passed through)
     *
     * @return array{lat: float, lng: float, id?: string}|string
     */
    protected function formatCoordinateAsObject(Coordinate|string|array $coordinate): array|string
    {
        if ($coordinate instanceof Coordinate) {
            return $coordinate->toArray();
        }

        if (is_string($coordinate)) {
            // Check if it's a coordinate string
            if ($this->isCoordinateString($coordinate)) {
                return Coordinate::fromString($coordinate)->toArray();
            }

            // Return as-is for addresses
            return $coordinate;
        }

        // Array format: [lat, lng] or [lat, lng, id]
        return Coordinate::fromArray($coordinate)->toArray();
    }

    /**
     * Check if a string looks like a coordinate (lat,lng or lat,lng,id).
     *
     * Validates that:
     * - At least 2 comma-separated parts exist
     * - First two parts are numeric (the coordinate values)
     * - Values are within valid geographic ranges
     */
    protected function isCoordinateString(string $value): bool
    {
        $parts = explode(',', $value);

        if (count($parts) < 2) {
            return false;
        }

        $lat = trim($parts[0]);
        $lng = trim($parts[1]);

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return false;
        }

        $latFloat = (float) $lat;
        $lngFloat = (float) $lng;

        // Validate lat/lng ranges
        return $latFloat >= -90 && $latFloat <= 90
            && $lngFloat >= -180 && $lngFloat <= 180;
    }

    /**
     * Normalize distance mode value, mapping haversine to straightline for backward compatibility
     */
    protected function normalizeDistanceMode(string|DistanceMode $mode): string
    {
        $value = $mode instanceof DistanceMode ? $mode->value : $mode;

        // Map haversine → straightline for backward compatibility
        return $value === 'haversine' ? 'straightline' : $value;
    }

    /**
     * Get string value from enum or string
     */
    protected function enumValue(string|DistanceUnits|DistanceOrderBy|DistanceSortOrder $value): string
    {
        if ($value instanceof DistanceUnits || $value instanceof DistanceOrderBy || $value instanceof DistanceSortOrder) {
            return $value->value;
        }

        return $value;
    }

    protected function getHeaders(): array
    {
        return [
            'Authorization' => sprintf('Bearer %s', $this->apiKey),
            'User-Agent' => sprintf('geocodio-library-php/%s', Geocodio::SDK_VERSION),
            'Accept' => 'application/json',
        ];
    }
}
