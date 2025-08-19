<?php

namespace Geocodio;

use Exception;
use Geocodio\Concerns\SendsRequests;
use Geocodio\Enums\GeocodeDirection;
use Geocodio\Exceptions\GeocodioException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;

class Geocodio
{
    use SendsRequests;

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
    private string $apiVersion = 'v1.9';

    /**
     * @var int Timeout for single geocoding requests in milliseconds
     */
    private int $singleTimeoutMs;

    /**
     * @var int Timeout for batch geocoding requests in milliseconds
     */
    private int $batchTimeoutMs;

    /**
     * @var int Timeout for lists API requests in milliseconds
     */
    private int $listsTimeoutMs;

    const ADDRESS_COMPONENT_PARAMETERS = [
        'street',
        'city',
        'state',
        'postal_code',
        'country',
    ];

    /**
     * @var Current SDK version
     */
    const SDK_VERSION = '2.3.0';

    /**
     * @var Timeout for single geocoding requests in milliseconds
     */
    const SINGLE_TIMEOUT_MS = 5000;

    /**
     * @var Timeout for batch geocoding requests in milliseconds
     */
    const BATCH_TIMEOUT_MS = 1800000; // 30 minutes

    /**
     * @var Timeout for lists API requests in milliseconds
     */
    const LISTS_TIMEOUT_MS = 60000;

    public function __construct(private readonly Client $client = new Client)
    {
        $this->apiKey = getenv('GEOCODIO_API_KEY');

        if ($hostname = getenv('GEOCODIO_HOSTNAME')) {
            $this->hostname = $hostname;
        }

        if ($apiVersion = getenv('GEOCODIO_API_VERSION')) {
            $this->apiVersion = $apiVersion;
        }

        // Initialize timeout values with defaults
        $this->singleTimeoutMs = self::SINGLE_TIMEOUT_MS;
        $this->batchTimeoutMs = self::BATCH_TIMEOUT_MS;
        $this->listsTimeoutMs = self::LISTS_TIMEOUT_MS;
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

    public function apiVersion(): string
    {
        return $this->apiVersion;
    }

    public function setSingleTimeoutMs(int $timeoutMs): self
    {
        $this->singleTimeoutMs = $timeoutMs;

        return $this;
    }

    public function setBatchTimeoutMs(int $timeoutMs): self
    {
        $this->batchTimeoutMs = $timeoutMs;

        return $this;
    }

    public function setListsTimeoutMs(int $timeoutMs): self
    {
        $this->listsTimeoutMs = $timeoutMs;

        return $this;
    }

    /**
     * Forward geocode an address or a list of addresses
     *
     * @see https://www.geocod.io/docs/#geocoding
     *
     * @param  string|array  $query
     */
    public function geocode(
        $query,
        array $fields = [],
        ?int $limit = null,
        ?string $format = null
    ): array {
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

            $response = $this->sendRequest('GET', 'geocode', $options, $this->singleTimeoutMs);
        } else {
            $options[RequestOptions::JSON] = $query;

            $response = $this->sendRequest('POST', 'geocode', $options, $this->batchTimeoutMs);
        }

        return $this->toResponse($response);
    }

    /**
     * Upload a list using a file on disk
     *
     * @see https://www.geocod.io/docs/#create-a-new-list
     */
    public function uploadList(
        string $file,
        GeocodeDirection $direction,
        string $format,
        ?string $callbackWebhook = null,
        array $fields = [],
    ): array {
        if (! file_exists($file)) {
            throw GeocodioException::fileNotFound($file);
        }

        $response = $this->uploadMultipartFile(
            $file,
            $direction,
            $format,
            $callbackWebhook,
            $fields,
        );

        return $this->toResponse($response);
    }

    /**
     * Upload a list using inline data
     *
     * @see https://www.geocod.io/docs/#create-a-new-list
     */
    public function uploadInlineList(
        string $data,
        string $filename,
        GeocodeDirection $direction,
        string $format,
        ?string $callbackWebhook = null,
        array $fields = [],
    ): array {
        $response = $this->uploadMultipartFile(
            $data,
            $direction,
            $format,
            $callbackWebhook,
            $fields,
            $filename,
        );

        return $this->toResponse($response);

    }

    /**
     * Get the status of an uploaded list
     *
     * @see https://www.geocod.io/docs/#see-list-status
     */
    public function listStatus(int $listId): mixed
    {
        $response = $this->sendRequest(
            'GET',
            "lists/{$listId}",
            [],
            $this->listsTimeoutMs
        );

        return $this->toResponse($response);
    }

    /**
     * Show all uploaded lists
     *
     * @see https://www.geocod.io/docs/#show-all-lists
     */
    public function lists(): mixed
    {
        $response = $this->sendRequest(
            'GET',
            'lists',
            [],
            $this->listsTimeoutMs
        );

        return $this->toResponse($response);
    }

    /**
     * Download a previsouly uploaded list that has been processed
     *
     * @see https://www.geocod.io/docs/#download-a-list
     */
    public function downloadList(int $listId, string $filePath): void
    {
        $response = $this->sendRequest(
            'GET',
            "lists/{$listId}/download",
            [
                RequestOptions::STREAM => true,
            ],
            $this->listsTimeoutMs
        );

        $body = $response->getBody();

        if (! $fileHandle = fopen($filePath, 'w')) {
            throw new Exception("Unable to open file for writing: {$filePath}");
        }

        while (! $body->eof()) {
            $chunk = $body->read(8192);
            fwrite($fileHandle, $chunk);
        }

        fclose($fileHandle);
    }

    /**
     * Delete a previously uploaded list
     *
     * @see https://www.geocod.io/docs/#delete-a-list
     */
    public function deleteList(int $listId): mixed
    {
        $response = $this->sendRequest(
            'DELETE',
            "lists/{$listId}",
            [],
            $this->listsTimeoutMs
        );

        return $this->toResponse($response);
    }

    /**
     * Reverse geocode a coordinate or a list of coordinates
     *
     * @see https://www.geocod.io/docs/#reverse-geocoding
     *
     * @param  string|array  $query
     */
    public function reverse(
        $query,
        array $fields = [],
        ?int $limit = null,
        ?string $format = null
    ): array {
        $options = [
            RequestOptions::QUERY => [
                'q' => $this->formattedReverseQuery($query),
                'fields' => implode(',', $fields),
                'limit' => $limit,
                'format' => $format,
            ],
        ];

        if (is_string($query) || (is_array($query) && is_numeric($query[0]))) {
            $response = $this->sendRequest('GET', 'reverse', $options, $this->singleTimeoutMs);
        } else {
            $options[RequestOptions::JSON] = array_map(fn ($q) => $this->formattedReverseQuery($q), $query);

            $response = $this->sendRequest('POST', 'reverse', $options, $this->batchTimeoutMs);

        }

        return $this->toResponse($response);

    }

    protected function isSingleQuery($query): bool
    {
        if (is_array($query)) {
            $addressComponentKeys = array_intersect(array_keys($query), self::ADDRESS_COMPONENT_PARAMETERS);

            return count($addressComponentKeys) >= 1;
        }

        return true;
    }

    protected function uploadMultipartFile(
        string $fileContents,
        GeocodeDirection $direction,
        string $format,
        ?string $callbackWebhook = null,
        array $fields = [],
        ?string $filename = null
    ): Response {
        if (is_file($fileContents) && ! file_exists($fileContents)) {
            throw GeocodioException::fileNotFound($fileContents);
        }

        $multipart = array_filter([
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
                'name' => 'fields',
                'contents' => implode(',', $fields),
            ],
            [
                'name' => 'callback',
                'contents' => $callbackWebhook,
            ],
        ], fn ($block) => $block['contents']);

        return $this->sendRequest('POST', 'lists', [RequestOptions::MULTIPART => $multipart], $this->listsTimeoutMs);
    }

    protected function toResponse(Response $response): array
    {
        return json_decode((string) $response->getBody(), true);
    }
}
