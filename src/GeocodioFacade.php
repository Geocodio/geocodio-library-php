<?php

namespace Geocodio;

use Illuminate\Support\Facades\Facade;

/**
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
