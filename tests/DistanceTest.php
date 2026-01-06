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
        expect($response['mode'])->toBe('straightline');
        expect($response['destinations'])->toHaveCount(2);
        expect($response['destinations'][0])->toHaveKeys(['location', 'distance_miles', 'distance_km']);
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

    it('can calculate straightline distances', function (): void {
        $response = $this->geocoder->distance(
            '37.7749,-122.4194',
            [
                '37.7849,-122.4094',
                '37.7949,-122.3994',
            ],
            'straightline'
        );

        expect($response['mode'])->toBe('straightline');
        expect($response['destinations'][0])->toHaveKeys(['location', 'distance_miles', 'distance_km']);
        expect($response['destinations'][0])->not->toHaveKey('duration_seconds');
    });

    it('can include custom IDs with coordinates', function (): void {
        $response = $this->geocoder->distance(
            '37.7749,-122.4194',
            [
                '37.7849,-122.4094,destA',
                '37.7949,-122.3994,destB',
            ],
            'driving'
        );

        expect($response['mode'])->toBe('driving');
        expect($response['destinations'][0]['id'])->toBe('destA');
        expect($response['destinations'][1]['id'])->toBe('destB');
    });
});

describe('Distance API - POST /distance-matrix (Standard Matrix)', function (): void {
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

        expect($response)->toHaveKeys(['mode', 'results']);
        expect($response['results'])->toHaveCount(2);
        expect($response['results'][0])->toHaveKeys(['origin', 'destinations']);
        expect($response['results'][0]['destinations'])->toHaveCount(2);
        expect($response['results'][0]['destinations'][0])->toHaveKeys(['location', 'distance_miles', 'distance_km']);
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

        expect($response['results'])->toHaveCount(2);
        expect($response['results'][0]['destinations'])->toHaveCount(2);
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

        expect($response)->toHaveKey('results');
    });

    it('can calculate straightline matrix', function (): void {
        $response = $this->geocoder->distanceMatrix(
            [
                '37.7749,-122.4194',
            ],
            [
                '37.7849,-122.4094',
            ],
            mode: 'straightline'
        );

        expect($response['mode'])->toBe('straightline');
    });
});

describe('Distance API - POST /distance-matrix (With Filters)', function (): void {
    it('can filter with max_results', function (): void {
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

        expect($response)->toHaveKeys(['mode', 'results']);
        expect($response['results'])->toHaveCount(1);
        expect($response['results'][0])->toHaveKeys(['origin', 'destinations']);
        expect($response['results'][0]['destinations'])->toHaveCount(2);
    });

    it('can filter with max_distance', function (): void {
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

    it('can sort results by distance', function (): void {
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
            orderBy: 'distance'
        );

        expect($response['results'])->toHaveCount(1);

        $distances = array_map(fn ($dest) => $dest['distance_miles'], $response['results'][0]['destinations']);
        $sortedDistances = $distances;
        sort($sortedDistances);

        expect($distances)->toBe($sortedDistances);
    });
});
