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
}
