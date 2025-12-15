<?php

namespace Geocodio\Tests;

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

describe('Distance API - GET /distance', function (): void {
    it('can calculate distances from origin to multiple destinations', function (): void {
        $response = $this->geocoder->distance(
            '37.7749,-122.4194',
            [
                '37.7849,-122.4094',
                '37.7949,-122.3994',
            ]
        );

        expect($response)->toHaveKey('origin');
        expect($response)->toHaveKey('mode');
        expect($response)->toHaveKey('destinations');
        expect($response)->toHaveKey('elements_billed');
        expect($response['mode'])->toBe('driving');
        expect($response['destinations'])->toHaveCount(2);
        expect($response['destinations'][0])->toHaveKeys(['location', 'distance_miles', 'distance_km', 'duration_seconds']);
    });

    it('can calculate distances with array coordinates', function (): void {
        $response = $this->geocoder->distance(
            [37.7749, -122.4194],
            [
                [37.7849, -122.4094],
                [37.7949, -122.3994],
            ]
        );

        expect($response)->toHaveKey('origin');
        expect($response['destinations'])->toHaveCount(2);
    });

    it('can calculate haversine distances', function (): void {
        $response = $this->geocoder->distance(
            '37.7749,-122.4194',
            [
                '37.7849,-122.4094',
                '37.7949,-122.3994',
            ],
            'haversine'
        );

        expect($response['mode'])->toBe('haversine');
        expect($response['destinations'][0])->toHaveKeys(['location', 'distance_miles', 'distance_km']);
        expect($response['destinations'][0])->not->toHaveKey('duration_seconds');
    });

    it('can include custom IDs with coordinates', function (): void {
        $response = $this->geocoder->distance(
            '37.7749,-122.4194,origin1',
            [
                '37.7849,-122.4094,dest1',
                '37.7949,-122.3994,dest2',
            ]
        );

        expect($response['destinations'][0]['id'])->toBe('dest1');
        expect($response['destinations'][1]['id'])->toBe('dest2');
    });
});

describe('Distance API - POST /distance (Standard Matrix)', function (): void {
    it('can calculate distance matrix', function (): void {
        $response = $this->geocoder->distanceMatrix(
            [
                '37.7749,-122.4194',
                '37.8049,-122.4294',
            ],
            [
                '37.7849,-122.4094',
                '37.7949,-122.3994',
            ]
        );

        expect($response)->toHaveKeys(['origins', 'destinations', 'mode', 'units', 'durations', 'distances', 'elements_billed']);
        expect($response['origins'])->toHaveCount(2);
        expect($response['destinations'])->toHaveCount(2);
        expect($response['durations'])->toHaveCount(2);
        expect($response['distances'])->toHaveCount(2);
        expect($response['durations'][0])->toHaveCount(2);
        expect($response['distances'][0])->toHaveCount(2);
    });

    it('can calculate matrix with array coordinates', function (): void {
        $response = $this->geocoder->distanceMatrix(
            [
                [37.7749, -122.4194],
                [37.8049, -122.4294],
            ],
            [
                [37.7849, -122.4094],
                [37.7949, -122.3994],
            ]
        );

        expect($response['origins'])->toHaveCount(2);
        expect($response['destinations'])->toHaveCount(2);
    });

    it('can calculate matrix with kilometers', function (): void {
        $response = $this->geocoder->distanceMatrix(
            [
                '37.7749,-122.4194',
            ],
            [
                '37.7849,-122.4094',
            ],
            units: 'km'
        );

        expect($response['units'])->toBe('km');
    });

    it('can calculate haversine matrix', function (): void {
        $response = $this->geocoder->distanceMatrix(
            [
                '37.7749,-122.4194',
            ],
            [
                '37.7849,-122.4094',
            ],
            mode: 'haversine'
        );

        expect($response['mode'])->toBe('haversine');
    });
});

describe('Distance API - POST /distance (Nearest Mode)', function (): void {
    it('can find nearest destinations with max_results', function (): void {
        $response = $this->geocoder->distanceMatrix(
            [
                '37.7749,-122.4194',
            ],
            [
                '37.7849,-122.4094',
                '37.7949,-122.3994',
                '37.8049,-122.4294',
            ],
            maxResults: 2
        );

        expect($response)->toHaveKeys(['origins', 'mode', 'units', 'results', 'elements_billed']);
        expect($response['results'])->toHaveCount(1);
        expect($response['results'][0])->toHaveKeys(['origin', 'destinations']);
        expect($response['results'][0]['destinations'])->toHaveCount(2);
    });

    it('can find nearest destinations with max_distance', function (): void {
        $response = $this->geocoder->distanceMatrix(
            [
                '37.7749,-122.4194',
            ],
            [
                '37.7849,-122.4094',
                '37.7949,-122.3994',
                '37.8049,-122.4294',
            ],
            maxDistance: 2.0
        );

        expect($response)->toHaveKey('results');
        foreach ($response['results'][0]['destinations'] as $dest) {
            if (isset($dest['distance_miles'])) {
                expect($dest['distance_miles'])->toBeLessThanOrEqual(2.0);
            }
        }
    });

    it('can find nearest destinations with max_duration', function (): void {
        $response = $this->geocoder->distanceMatrix(
            [
                '37.7749,-122.4194',
            ],
            [
                '37.7849,-122.4094',
                '37.7949,-122.3994',
            ],
            maxDuration: 300
        );

        expect($response)->toHaveKey('results');
        foreach ($response['results'][0]['destinations'] as $dest) {
            expect($dest['duration_seconds'])->toBeLessThanOrEqual(300);
        }
    });

    it('can sort results by duration', function (): void {
        $response = $this->geocoder->distanceMatrix(
            [
                '37.7749,-122.4194',
            ],
            [
                '37.7849,-122.4094',
                '37.7949,-122.3994',
                '37.8049,-122.4294',
            ],
            maxResults: 3,
            orderBy: 'duration'
        );

        expect($response['results'])->toHaveCount(1);

        $durations = array_map(fn ($dest) => $dest['duration_seconds'], $response['results'][0]['destinations']);
        $sortedDurations = $durations;
        sort($sortedDurations);

        expect($durations)->toBe($sortedDurations);
    });
});
