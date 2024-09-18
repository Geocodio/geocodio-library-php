<?php

namespace Geocodio;

use Exception;
use Geocodio\Enums\GeocodeDirection;
use Geocodio\Exceptions\GeocodioException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Throwable;

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

    const SDK_VERSION = '1.2.0';

    public function __construct(private readonly Client $client = new Client)
    {
        $this->apiKey = getenv('GEOCODIO_API_KEY');

        if ($hostname = getenv('GEOCODIO_HOSTNAME')) {
            $this->hostname = $hostname;
        }

        if ($apiVersion = getenv('GEOCODIO_API_VERSION')) {
            $this->apiVersion = $apiVersion;
        }
    }

    public function apiVersion(): string
    {
        return $this->apiVersion;
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
    public function geocode(
        $query,
        array $fields = [],
        ?int $limit = null,
        ?string $format = null
    ): mixed {
        $options = [
            RequestOptions::QUERY => [
                'fields' => implode(',', $fields),
                'limit' => $limit,
                'format' => $format,
            ],
        ];

        if ($this->isSingleQuery($query)) {
            $query = is_array($query) ? $query : ['q' => $query];
            $options[RequestOptions::QUERY] = array_merge($options[RequestOptions::QUERY], $query);

            $response = $this->sendRequest('GET', 'geocode', $options);
        } else {
            $options[RequestOptions::JSON] = $query;

            $response = $this->sendRequest('POST', 'geocode', $options);
        }

        return json_decode((string) $response->getBody());
    }

    public function uploadList(
        string $file,
        GeocodeDirection $direction,
        string $format,
        string $callbackWebhook = '',
    ): array {
        if (! file_exists($file)) {
            throw GeocodioException::fileNotFound($file);
        }

        $response = $this->uploadMultipartFile(
            $file,
            $direction,
            $format,
            $callbackWebhook,
        );

        return json_decode((string) $response->getBody(), true);
    }

    private function uploadMultipartFile(
        string $fileContents,
        GeocodeDirection $direction,
        string $format,
        string $callbackWebhook,
        ?string $filename = null
    ): Response {
        if (is_file($fileContents) && ! file_exists($fileContents)) {
            throw GeocodioException::fileNotFound($fileContents);
        }

        $multipart = [
            [
                'name' => 'file',
                'contents' => is_file($fileContents) ? fopen($fileContents, 'r') : $fileContents,
                'filename' => $filename ?: basename($fileContents),
            ],
            [
                'name' => 'direction',
                'contents' => $direction->value,
            ],
            [
                'name' => 'format',
                'contents' => $format,
            ],
            [
                'name' => 'callback',
                'contents' => $callbackWebhook,
            ],
        ];

        return $this->sendRequest('POST', 'lists', [RequestOptions::MULTIPART => $multipart]);
    }

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

            $response = json_decode($e->getResponse()->getBody(), true);

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

    public function uploadInlineList(
        string $data,
        string $filename,
        GeocodeDirection $direction,
        string $format,
        string $callbackWebhook = '',
    ): array {
        $response = $this->uploadMultipartFile(
            $data,
            $direction,
            $format,
            $callbackWebhook,
            $filename,
        );

        return json_decode((string) $response->getBody(), true);
    }

    public function listStatus(int $listId): mixed
    {
        $response = $this->sendRequest(
            'GET',
            "lists/{$listId}",
        );

        return json_decode((string) $response->getBody(), true);
    }

    public function lists(): mixed
    {
        $response = $this->sendRequest(
            'GET',
            'lists',
        );

        return json_decode((string) $response->getBody(), true);
    }

    public function downloadList(int $listId, string $filePath): void
    {
        $response = $this->sendRequest(
            'GET',
            "lists/{$listId}/download",
            [
                RequestOptions::STREAM => true,
            ]
        );

        $body = $response->getBody();

        if (! $fileHandle = fopen($filePath, 'w')) {
            throw new Exception("Unable to open file for writing: {$filePath}");
        }

        while (! $body->eof()) {
            $chunk = $body->read(8192); // Read in 8KB chunks
            fwrite($fileHandle, $chunk);
        }

        fclose($fileHandle);
    }

    public function deleteList(int $listId): mixed
    {
        $response = $this->sendRequest(
            'DELETE',
            "lists/{$listId}",
        );

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Reverse geocode a coordinate or a list of coordinates
     *
     * @param  string|array  $query
     * @return array|object
     */
    public function reverse(
        $query,
        array $fields = [],
        ?int $limit = null,
        ?string $format = null
    ): mixed {
        $options = [
            RequestOptions::QUERY => [
                'q' => $this->formattedReverseQuery($query),
                'fields' => implode(',', $fields),
                'limit' => $limit,
                'format' => $format,
            ],
        ];

        if (is_string($query) || (is_array($query) && is_numeric($query[0]))) {
            $response = $this->sendRequest('GET', 'reverse', $options);
        } else {
            $options[RequestOptions::JSON] = array_map(fn ($q) => $this->formattedReverseQuery($q), $query);

            $response = $this->sendRequest('POST', 'reverse', $options);

        }

        return json_decode((string) $response->getBody());
    }

    private function formatUrl(string $endpoint): string
    {
        return vsprintf('https://%s/%s/%s', [
            $this->hostname,
            $this->apiVersion,
            $endpoint,
        ]);
    }

    private function formattedReverseQuery($query)
    {
        if (is_array($query) && count($query) === 2) {
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

    private function getHeaders(): array
    {
        return [
            'User-Agent' => sprintf('geocodio-library-php/%s', self::SDK_VERSION),
            'Accept' => 'application/json',
        ];
    }
}
