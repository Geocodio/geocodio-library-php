<?php

namespace Geocodio;

use Exception;
use Geocodio\Concerns\SendsRequests;
use Geocodio\Data\Coordinate;
use Geocodio\Enums\DistanceMode;
use Geocodio\Enums\DistanceOrderBy;
use Geocodio\Enums\DistanceSortOrder;
use Geocodio\Enums\DistanceUnits;
use Geocodio\Enums\GeocodeDirection;
use Geocodio\Exceptions\GeocodioException;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

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

    /**
     * @var int Timeout for distance API requests in milliseconds
     */
    private int $distanceTimeoutMs;

    /**
     * @var int Timeout for list download requests in milliseconds
     */
    private int $listDownloadTimeoutMs;

    const ADDRESS_COMPONENT_PARAMETERS = [
        'street',
        'city',
        'state',
        'postal_code',
        'country',
    ];

    /**
     * Current SDK version
     */
    const SDK_VERSION = '2.6.0';

    /**
     * Timeout for single geocoding requests in milliseconds
     */
    const SINGLE_TIMEOUT_MS = 5000;

    /**
     * Timeout for batch geocoding requests in milliseconds
     */
    const BATCH_TIMEOUT_MS = 1800000; // 30 minutes

    /**
     * Timeout for lists API requests in milliseconds
     */
    const LISTS_TIMEOUT_MS = 60000;

    /**
     * Timeout for distance API requests in milliseconds
     */
    const DISTANCE_TIMEOUT_MS = 10000;

    /**
     * @var Timeout for list download requests in milliseconds
     */
    const LIST_DOWNLOAD_TIMEOUT_MS = 1800000; // 30 minutes

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
        $this->distanceTimeoutMs = self::DISTANCE_TIMEOUT_MS;
        $this->listDownloadTimeoutMs = self::LIST_DOWNLOAD_TIMEOUT_MS;
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

    public function setDistanceTimeoutMs(int $timeoutMs): self
    {
        $this->distanceTimeoutMs = $timeoutMs;

        return $this;
    }

    public function setListDownloadTimeoutMs(int $timeoutMs): self
    {
        $this->listDownloadTimeoutMs = $timeoutMs;

        return $this;
    }

    /**
     * Forward geocode an address or a list of addresses
     *
     * @see https://www.geocod.io/docs/#geocoding
     *
     * @param  string|array  $query
     * @param  array  $fields  Additional data fields to append
     * @param  int|null  $limit  Maximum number of results
     * @param  string|null  $format  Response format
     * @param  array  $destinations  Optional destinations for distance calculation
     * @param  string|DistanceMode  $distanceMode  Distance mode: "driving", "straightline", or "haversine"
     * @param  string|DistanceUnits  $distanceUnits  Distance units: "miles" or "km"
     * @param  int|null  $distanceMaxResults  Maximum number of destinations to return
     * @param  float|null  $distanceMaxDistance  Maximum distance filter
     * @param  int|null  $distanceMaxDuration  Maximum duration filter (seconds, driving mode only)
     * @param  float|null  $distanceMinDistance  Minimum distance filter
     * @param  int|null  $distanceMinDuration  Minimum duration filter (seconds, driving mode only)
     * @param  string|DistanceOrderBy  $distanceOrderBy  Sort by "distance" or "duration"
     * @param  string|DistanceSortOrder  $distanceSortOrder  Sort order: "asc" or "desc"
     */
    public function geocode(
        $query,
        array $fields = [],
        ?int $limit = null,
        ?string $format = null,
        array $destinations = [],
        string|DistanceMode $distanceMode = DistanceMode::Straightline,
        string|DistanceUnits $distanceUnits = DistanceUnits::Miles,
        ?int $distanceMaxResults = null,
        ?float $distanceMaxDistance = null,
        ?int $distanceMaxDuration = null,
        ?float $distanceMinDistance = null,
        ?int $distanceMinDuration = null,
        string|DistanceOrderBy $distanceOrderBy = DistanceOrderBy::Distance,
        string|DistanceSortOrder $distanceSortOrder = DistanceSortOrder::Asc
    ): array {
        $options = [
            RequestOptions::QUERY => [
                'fields' => implode(',', $fields),
                'limit' => $limit,
                'format' => $format,
            ],
        ];

        // Add distance parameters if destinations are provided
        // Destinations can be either coordinates or addresses
        if ($destinations !== []) {
            foreach ($destinations as $destination) {
                $options[RequestOptions::QUERY]['destinations'][] = $this->formatCoordinateAsString($destination);
            }
            $options[RequestOptions::QUERY]['distance_mode'] = $this->normalizeDistanceMode($distanceMode);
            $options[RequestOptions::QUERY]['distance_units'] = $this->enumValue($distanceUnits);

            // Add optional distance filter parameters
            if ($distanceMaxResults !== null) {
                $options[RequestOptions::QUERY]['distance_max_results'] = $distanceMaxResults;
            }
            if ($distanceMaxDistance !== null) {
                $options[RequestOptions::QUERY]['distance_max_distance'] = $distanceMaxDistance;
            }
            if ($distanceMaxDuration !== null) {
                $options[RequestOptions::QUERY]['distance_max_duration'] = $distanceMaxDuration;
            }
            if ($distanceMinDistance !== null) {
                $options[RequestOptions::QUERY]['distance_min_distance'] = $distanceMinDistance;
            }
            if ($distanceMinDuration !== null) {
                $options[RequestOptions::QUERY]['distance_min_duration'] = $distanceMinDuration;
            }

            // Add sorting parameters when filters are used
            if ($distanceMaxResults !== null || $distanceMaxDistance !== null || $distanceMaxDuration !== null || $distanceMinDistance !== null || $distanceMinDuration !== null) {
                $options[RequestOptions::QUERY]['distance_order_by'] = $this->enumValue($distanceOrderBy);
                $options[RequestOptions::QUERY]['distance_sort_order'] = $this->enumValue($distanceSortOrder);
            }
        }

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
            $this->listDownloadTimeoutMs
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
     * @param  array  $fields  Additional data fields to append
     * @param  int|null  $limit  Maximum number of results
     * @param  string|null  $format  Response format
     * @param  array  $destinations  Optional destinations for distance calculation
     * @param  string|DistanceMode  $distanceMode  Distance mode: "driving", "straightline", or "haversine"
     * @param  string|DistanceUnits  $distanceUnits  Distance units: "miles" or "km"
     * @param  int|null  $distanceMaxResults  Maximum number of destinations to return
     * @param  float|null  $distanceMaxDistance  Maximum distance filter
     * @param  int|null  $distanceMaxDuration  Maximum duration filter (seconds, driving mode only)
     * @param  float|null  $distanceMinDistance  Minimum distance filter
     * @param  int|null  $distanceMinDuration  Minimum duration filter (seconds, driving mode only)
     * @param  string|DistanceOrderBy  $distanceOrderBy  Sort by "distance" or "duration"
     * @param  string|DistanceSortOrder  $distanceSortOrder  Sort order: "asc" or "desc"
     */
    public function reverse(
        $query,
        array $fields = [],
        ?int $limit = null,
        ?string $format = null,
        array $destinations = [],
        string|DistanceMode $distanceMode = DistanceMode::Straightline,
        string|DistanceUnits $distanceUnits = DistanceUnits::Miles,
        ?int $distanceMaxResults = null,
        ?float $distanceMaxDistance = null,
        ?int $distanceMaxDuration = null,
        ?float $distanceMinDistance = null,
        ?int $distanceMinDuration = null,
        string|DistanceOrderBy $distanceOrderBy = DistanceOrderBy::Distance,
        string|DistanceSortOrder $distanceSortOrder = DistanceSortOrder::Asc
    ): array {
        $options = [
            RequestOptions::QUERY => [
                'q' => $this->formattedReverseQuery($query),
                'fields' => implode(',', $fields),
                'limit' => $limit,
                'format' => $format,
            ],
        ];

        // Add distance parameters if destinations are provided
        // Destinations can be either coordinates or addresses
        if ($destinations !== []) {
            foreach ($destinations as $destination) {
                $options[RequestOptions::QUERY]['destinations'][] = $this->formatCoordinateAsString($destination);
            }
            $options[RequestOptions::QUERY]['distance_mode'] = $this->normalizeDistanceMode($distanceMode);
            $options[RequestOptions::QUERY]['distance_units'] = $this->enumValue($distanceUnits);

            // Add optional distance filter parameters
            if ($distanceMaxResults !== null) {
                $options[RequestOptions::QUERY]['distance_max_results'] = $distanceMaxResults;
            }
            if ($distanceMaxDistance !== null) {
                $options[RequestOptions::QUERY]['distance_max_distance'] = $distanceMaxDistance;
            }
            if ($distanceMaxDuration !== null) {
                $options[RequestOptions::QUERY]['distance_max_duration'] = $distanceMaxDuration;
            }
            if ($distanceMinDistance !== null) {
                $options[RequestOptions::QUERY]['distance_min_distance'] = $distanceMinDistance;
            }
            if ($distanceMinDuration !== null) {
                $options[RequestOptions::QUERY]['distance_min_duration'] = $distanceMinDuration;
            }

            // Add sorting parameters when filters are used
            if ($distanceMaxResults !== null || $distanceMaxDistance !== null || $distanceMaxDuration !== null || $distanceMinDistance !== null || $distanceMinDuration !== null) {
                $options[RequestOptions::QUERY]['distance_order_by'] = $this->enumValue($distanceOrderBy);
                $options[RequestOptions::QUERY]['distance_sort_order'] = $this->enumValue($distanceSortOrder);
            }
        }

        if (is_string($query) || (is_array($query) && is_numeric($query[0]))) {
            $response = $this->sendRequest('GET', 'reverse', $options, $this->singleTimeoutMs);
        } else {
            $options[RequestOptions::JSON] = array_map(fn ($q) => $this->formattedReverseQuery($q), $query);

            $response = $this->sendRequest('POST', 'reverse', $options, $this->batchTimeoutMs);

        }

        return $this->toResponse($response);

    }

    /**
     * Calculate distances from a single origin to multiple destinations
     *
     * @see https://www.geocod.io/docs/#distance
     *
     * @param  Coordinate|string|array  $origin  Single coordinate or address
     * @param  array<Coordinate|string|array>  $destinations  Array of coordinates or addresses
     * @param  string|DistanceMode  $mode  Distance mode: "driving", "straightline", or "haversine" (alias for straightline)
     * @param  string|DistanceUnits  $units  Distance units: "miles" or "km"
     * @param  int|null  $maxResults  Maximum number of destinations to return
     * @param  float|null  $maxDistance  Maximum distance filter
     * @param  int|null  $maxDuration  Maximum duration filter (seconds, driving mode only)
     * @param  float|null  $minDistance  Minimum distance filter
     * @param  int|null  $minDuration  Minimum duration filter (seconds, driving mode only)
     * @param  string|DistanceOrderBy  $orderBy  Sort by "distance" or "duration"
     * @param  string|DistanceSortOrder  $sortOrder  Sort order: "asc" or "desc"
     */
    public function distance(
        Coordinate|string|array $origin,
        array $destinations,
        string|DistanceMode $mode = DistanceMode::Straightline,
        string|DistanceUnits $units = DistanceUnits::Miles,
        ?int $maxResults = null,
        ?float $maxDistance = null,
        ?int $maxDuration = null,
        ?float $minDistance = null,
        ?int $minDuration = null,
        string|DistanceOrderBy $orderBy = DistanceOrderBy::Distance,
        string|DistanceSortOrder $sortOrder = DistanceSortOrder::Asc
    ): array {
        $queryParams = [
            'origin' => $this->formatCoordinateAsString($origin),
            'mode' => $this->normalizeDistanceMode($mode),
            'units' => $this->enumValue($units),
        ];

        // Add optional filter parameters
        if ($maxResults !== null) {
            $queryParams['max_results'] = $maxResults;
        }

        if ($maxDistance !== null) {
            $queryParams['max_distance'] = $maxDistance;
        }

        if ($maxDuration !== null) {
            $queryParams['max_duration'] = $maxDuration;
        }

        if ($minDistance !== null) {
            $queryParams['min_distance'] = $minDistance;
        }

        if ($minDuration !== null) {
            $queryParams['min_duration'] = $minDuration;
        }

        // Add sorting parameters when filters are used
        if ($maxResults !== null || $maxDistance !== null || $maxDuration !== null || $minDistance !== null || $minDuration !== null) {
            $queryParams['order_by'] = $this->enumValue($orderBy);
            $queryParams['sort_order'] = $this->enumValue($sortOrder);
        }

        // Build query string manually to ensure destinations[] format (not destinations[0])
        // Also preserve commas unencoded as the API expects them that way for custom IDs
        $queryString = http_build_query($queryParams);
        foreach ($destinations as $destination) {
            $encoded = urlencode($this->formatCoordinateAsString($destination));
            // Decode commas back to literal commas as the API expects
            $encoded = str_replace('%2C', ',', $encoded);
            $queryString .= '&destinations[]='.$encoded;
        }

        $response = $this->sendRequest('GET', 'distance?'.$queryString, [], $this->distanceTimeoutMs);

        return $this->toResponse($response);
    }

    /**
     * Calculate distance matrix (multiple origins × destinations)
     *
     * @see https://www.geocod.io/docs/#distance
     *
     * @param  array<Coordinate|string|array>  $origins  Array of coordinates or addresses
     * @param  array<Coordinate|string|array>  $destinations  Array of coordinates or addresses
     * @param  string|DistanceMode  $mode  Distance mode: "driving", "straightline", or "haversine" (alias for straightline)
     * @param  string|DistanceUnits  $units  Distance units: "miles" or "km"
     * @param  int|null  $maxResults  Maximum number of destinations to return per origin
     * @param  float|null  $maxDistance  Maximum distance filter
     * @param  int|null  $maxDuration  Maximum duration filter (seconds, driving mode only)
     * @param  float|null  $minDistance  Minimum distance filter
     * @param  int|null  $minDuration  Minimum duration filter (seconds, driving mode only)
     * @param  string|DistanceOrderBy  $orderBy  Sort by "distance" or "duration"
     * @param  string|DistanceSortOrder  $sortOrder  Sort order: "asc" or "desc"
     */
    public function distanceMatrix(
        array $origins,
        array $destinations,
        string|DistanceMode $mode = DistanceMode::Straightline,
        string|DistanceUnits $units = DistanceUnits::Miles,
        ?int $maxResults = null,
        ?float $maxDistance = null,
        ?int $maxDuration = null,
        ?float $minDistance = null,
        ?int $minDuration = null,
        string|DistanceOrderBy $orderBy = DistanceOrderBy::Distance,
        string|DistanceSortOrder $sortOrder = DistanceSortOrder::Asc
    ): array {
        // Format coordinates as objects for POST request (addresses pass through as strings)
        $formattedOrigins = array_map(
            fn ($coord): string|array => $this->formatCoordinateAsObject($coord),
            $origins
        );
        $formattedDestinations = array_map(
            fn ($coord): string|array => $this->formatCoordinateAsObject($coord),
            $destinations
        );

        $payload = [
            'origins' => $formattedOrigins,
            'destinations' => $formattedDestinations,
            'mode' => $this->normalizeDistanceMode($mode),
            'units' => $this->enumValue($units),
        ];

        // Add optional filter parameters
        if ($maxResults !== null) {
            $payload['max_results'] = $maxResults;
        }

        if ($maxDistance !== null) {
            $payload['max_distance'] = $maxDistance;
        }

        if ($maxDuration !== null) {
            $payload['max_duration'] = $maxDuration;
        }

        if ($minDistance !== null) {
            $payload['min_distance'] = $minDistance;
        }

        if ($minDuration !== null) {
            $payload['min_duration'] = $minDuration;
        }

        // Add sorting parameters when filters are used
        if ($maxResults !== null || $maxDistance !== null || $maxDuration !== null || $minDistance !== null || $minDuration !== null) {
            $payload['order_by'] = $this->enumValue($orderBy);
            $payload['sort_order'] = $this->enumValue($sortOrder);
        }

        $options = [
            RequestOptions::JSON => $payload,
        ];

        $response = $this->sendRequest('POST', 'distance-matrix', $options, $this->distanceTimeoutMs);

        return $this->toResponse($response);
    }

    /**
     * Create an async distance matrix job
     *
     * @see https://www.geocod.io/docs/#distance-matrix
     *
     * @param  string  $name  Job name for identification
     * @param  int|array  $origins  List ID or array of coordinates/addresses
     * @param  int|array  $destinations  List ID or array of coordinates/addresses
     * @param  string|DistanceMode  $mode  Distance mode: "driving", "straightline", or "haversine"
     * @param  string|DistanceUnits  $units  Distance units: "miles" or "km"
     * @param  int|null  $maxResults  Maximum number of destinations to return per origin
     * @param  float|null  $maxDistance  Maximum distance filter
     * @param  int|null  $maxDuration  Maximum duration filter (seconds, driving mode only)
     * @param  float|null  $minDistance  Minimum distance filter
     * @param  int|null  $minDuration  Minimum duration filter (seconds, driving mode only)
     * @param  string|DistanceOrderBy  $orderBy  Sort by "distance" or "duration"
     * @param  string|DistanceSortOrder  $sortOrder  Sort order: "asc" or "desc"
     * @param  string|null  $callbackUrl  Optional webhook URL for job completion notification
     */
    public function createDistanceMatrixJob(
        string $name,
        int|array $origins,
        int|array $destinations,
        string|DistanceMode $mode = DistanceMode::Straightline,
        string|DistanceUnits $units = DistanceUnits::Miles,
        ?int $maxResults = null,
        ?float $maxDistance = null,
        ?int $maxDuration = null,
        ?float $minDistance = null,
        ?int $minDuration = null,
        string|DistanceOrderBy $orderBy = DistanceOrderBy::Distance,
        string|DistanceSortOrder $sortOrder = DistanceSortOrder::Asc,
        ?string $callbackUrl = null
    ): array {
        $payload = [
            'name' => $name,
            'mode' => $this->normalizeDistanceMode($mode),
            'units' => $this->enumValue($units),
        ];

        // Handle origins - can be list ID or array of coordinates/addresses
        if (is_int($origins)) {
            $payload['origins'] = $origins;
        } else {
            $payload['origins'] = array_map(
                fn ($coord): string|array => $this->formatCoordinateAsObject($coord),
                $origins
            );
        }

        // Handle destinations - can be list ID or array of coordinates/addresses
        if (is_int($destinations)) {
            $payload['destinations'] = $destinations;
        } else {
            $payload['destinations'] = array_map(
                fn ($coord): string|array => $this->formatCoordinateAsObject($coord),
                $destinations
            );
        }

        // Add optional filter parameters
        if ($maxResults !== null) {
            $payload['max_results'] = $maxResults;
        }
        if ($maxDistance !== null) {
            $payload['max_distance'] = $maxDistance;
        }
        if ($maxDuration !== null) {
            $payload['max_duration'] = $maxDuration;
        }
        if ($minDistance !== null) {
            $payload['min_distance'] = $minDistance;
        }
        if ($minDuration !== null) {
            $payload['min_duration'] = $minDuration;
        }

        // Add sorting parameters when filters are used
        if ($maxResults !== null || $maxDistance !== null || $maxDuration !== null || $minDistance !== null || $minDuration !== null) {
            $payload['order_by'] = $this->enumValue($orderBy);
            $payload['sort_order'] = $this->enumValue($sortOrder);
        }

        if ($callbackUrl !== null) {
            $payload['callback'] = $callbackUrl;
        }

        $response = $this->sendRequest(
            'POST',
            'distance-jobs',
            [RequestOptions::JSON => $payload],
            $this->listsTimeoutMs
        );

        return $this->toResponse($response);
    }

    /**
     * Get the status of a distance matrix job
     *
     * @see https://www.geocod.io/docs/#distance-matrix
     */
    public function distanceMatrixJobStatus(string $identifier): array
    {
        $response = $this->sendRequest(
            'GET',
            "distance-jobs/{$identifier}",
            [],
            $this->listsTimeoutMs
        );

        return $this->toResponse($response);
    }

    /**
     * List all distance matrix jobs
     *
     * @see https://www.geocod.io/docs/#distance-matrix
     */
    public function distanceMatrixJobs(?int $page = null): array
    {
        $options = [];

        if ($page !== null) {
            $options[RequestOptions::QUERY] = ['page' => $page];
        }

        $response = $this->sendRequest(
            'GET',
            'distance-jobs',
            $options,
            $this->listsTimeoutMs
        );

        return $this->toResponse($response);
    }

    /**
     * Download the results of a completed distance matrix job
     *
     * @see https://www.geocod.io/docs/#distance-matrix
     */
    public function downloadDistanceMatrixJob(string $identifier, string $filePath): void
    {
        $response = $this->sendRequest(
            'GET',
            "distance-jobs/{$identifier}/download",
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
     * Get the results of a completed distance matrix job as parsed array
     *
     * Returns the same format as the distance POST endpoint.
     *
     * @see https://www.geocod.io/docs/#distance-matrix
     */
    public function getDistanceMatrixJobResults(string $identifier): array
    {
        $response = $this->sendRequest(
            'GET',
            "distance-jobs/{$identifier}/download",
            [],
            $this->listsTimeoutMs
        );

        return $this->toResponse($response);
    }

    /**
     * Delete a distance matrix job
     *
     * @see https://www.geocod.io/docs/#distance-matrix
     */
    public function deleteDistanceMatrixJob(string $identifier): array
    {
        $response = $this->sendRequest(
            'DELETE',
            "distance-jobs/{$identifier}",
            [],
            $this->listsTimeoutMs
        );

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
    ): ResponseInterface {
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

    protected function toResponse(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true);
    }
}
