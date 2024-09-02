<?php

namespace Geocodio;

use Illuminate\Support\Facades\Facade;

/**
 * @method static Geocodio setApiKey(string $apiKey)
 * @method static Geocodio setHostname(string $hostname)
 * @method static Geocodio setApiVersion(string $apiVersion)
 * @method static array|object geocode(string|array $query, array $fields = [], int $limit = null, string $format = null)
 * @method static array|object reverse(string|array $query, array $fields = [], int $limit = null, string $format = null)
 *
 * @see \Geocodio\GeocodioClass
 */
class GeocodioFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'geocodio';
    }
}
