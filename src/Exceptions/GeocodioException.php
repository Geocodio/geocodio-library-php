<?php

namespace Geocodio\Exceptions;

use Exception;
use Throwable;

class GeocodioException extends Exception
{
    /**
     * Non-fatal advisories returned alongside the error, from the `_warnings` response key.
     *
     * @var array<int, string>
     */
    private array $warnings = [];

    public static function fileNotFound(string $filename, ?Throwable $previous = null): GeocodioException
    {
        return new GeocodioException(
            sprintf('File (%s) not found', $filename),
            previous: $previous
        );
    }

    /**
     * @param  array<int, string>  $warnings
     */
    public static function requestError(string $message, ?Throwable $previous = null, array $warnings = []): GeocodioException
    {
        $exception = new GeocodioException(
            sprintf('Request Error: %s', $message),
            previous: $previous
        );

        $exception->warnings = $warnings;

        return $exception;
    }

    /**
     * Non-fatal advisories the API returned alongside the error.
     *
     * The API appends a `_warnings` key to error responses just as it does to
     * successful ones, e.g. to point out a misspelled field name in a request
     * that failed for an unrelated reason.
     *
     * @return array<int, string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }
}
