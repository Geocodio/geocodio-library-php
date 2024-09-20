<?php

namespace Geocodio\Tests;

use Geocodio\Exceptions\GeocodioException;
use Geocodio\Geocodio;

uses(InteractsWithAPI::class);

beforeEach(function (): void {
    $this->geocoder = new Geocodio;
    $this->geocoder->setApiKey($this->getApiKeyFromEnvironment());

    $hostname = $this->getHostnameFromEnvironment();
    if ($hostname) {
        $this->geocoder->setHostname($hostname);
    }
});

it('throws an exception for a bad API key', function (): void {
    $geocoder = new Geocodio;
    $geocoder->setApiKey('BAD_API_KEY');

    expect(fn (): mixed => $geocoder->geocode('20003'))
        ->toThrow(GeocodioException::class, 'Invalid API key');
});

it('throws an exception for a bad query', function (): void {
    expect(fn () => $this->geocoder->geocode(' '))
        ->toThrow(GeocodioException::class, 'Could not geocode address. No matches found.');
});
