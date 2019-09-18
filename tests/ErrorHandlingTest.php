<?php

namespace Geocodio\Tests;

use PHPUnit\Framework\TestCase;
use Geocodio\Geocodio;
use Geocodio\Exceptions\GeocodioException;

class ErrorHandlingTest extends TestCase
{
    use InteractsWithAPI;

    /** @var Geocodio */
    private $gecoder;

    public function setUp(): void
    {
        parent::setUp();

        $this->geocoder = new Geocodio();
        $this->geocoder->setApiKey($this->getApiKeyFromEnvironment());

        $hostname = $this->getHostnameFromEnvironment();
        if ($hostname) {
            $this->geocoder->setHostname($hostname);
        }
    }

    public function testBadApiKey()
    {
        $this->expectException(GeocodioException::class);
        $this->expectExceptionMessage('Invalid API key');

        $geocoder = new Geocodio();
        $geocoder->setApiKey('BAD_API_KEY');
        $geocoder->geocode('20003');
    }

    public function testBadQuery()
    {
        $this->expectException(GeocodioException::class);
        $this->expectExceptionMessage('Could not geocode address. Postal code or city required.');

        $this->geocoder->geocode(' ');
    }
}
