<?php

namespace Geocodio\Exceptions;

use Exception;
use Throwable;

class GeocodioException extends Exception
{
    public static function fileNotFound(string $filename, ?Throwable $previous = null): GeocodioException
    {
        return new GeocodioException(
            sprintf('File (%s) not found', $filename),
            previous: $previous
        );
    }

    public static function requestError(string $message, ?Throwable $previous = null): GeocodioException
    {
        return new GeocodioException(
            sprintf('Request Error: %s', $message),
            previous: $previous
        );
    }
}
