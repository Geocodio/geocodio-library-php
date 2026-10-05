<?php

declare(strict_types=1);

namespace Geocodio\Tests;

use Geocodio\Enums\GeocodeDirection;
use Geocodio\Exceptions\GeocodioException;
use Geocodio\Geocodio;
use Geocodio\Tests\TestDoubles\Client;
use GuzzleHttp\Psr7\Response;

/*
|--------------------------------------------------------------------------
| Warnings passthrough
|--------------------------------------------------------------------------
|
| The API reports non-fatal advisories under a `_warnings` key -- an
| unrecognized field name, a superseded API version, an append that was
| skipped. The library returns decoded responses verbatim, so the key is
| already available to callers. These tests lock that in: they fail if a
| future typed-response refactor drops the key on any response shape.
|
| The shapes are the ones the OpenAPI specification models (see the
| `Warnings` schema): top-level on single geocode/reverse, per result,
| per batch item, and on the lists and distance-jobs responses.
|
*/

/**
 * Build a geocoder backed by a mock HTTP client returning the given payloads.
 */
function geocoderReturning(array ...$payloads): Geocodio
{
    $responses = array_map(
        fn (array $payload): Response => new Response(200, ['Content-Type' => 'application/json'], json_encode($payload)),
        $payloads
    );

    $geocoder = new Geocodio(Client::create($responses)->client());
    $geocoder->setApiKey('test-key');

    return $geocoder;
}

describe('Geocoding responses', function (): void {
    it('preserves top-level warnings on a single forward geocode', function (): void {
        $geocoder = geocoderReturning([
            'results' => [
                ['formatted_address' => '1109 N Highland St, Arlington, VA 22201'],
            ],
            '_warnings' => [
                'The field congress is not recognized. Did you mean cd?',
            ],
        ]);

        $response = $geocoder->geocode('1109 N Highland St, Arlington VA', fields: ['congress']);

        expect($response)->toHaveKey('_warnings');
        expect($response['_warnings'])
            ->toBe(['The field congress is not recognized. Did you mean cd?']);
    });

    it('preserves top-level warnings on a single reverse geocode', function (): void {
        $geocoder = geocoderReturning([
            'results' => [
                ['formatted_address' => '1109 N Highland St, Arlington, VA 22201'],
            ],
            '_warnings' => [
                'Ignoring parameter zipcode as it was not expected. Did you mean postal_code?',
            ],
        ]);

        $response = $geocoder->reverse('38.886665,-77.094733');

        expect($response['_warnings'])
            ->toBe(['Ignoring parameter zipcode as it was not expected. Did you mean postal_code?']);
    });

    it('preserves per-result warnings', function (): void {
        $geocoder = geocoderReturning([
            'results' => [
                [
                    'formatted_address' => 'Arlington, VA 22201',
                    'accuracy_type' => 'place',
                    '_warnings' => ['ffiec field was skipped since result is not street-level'],
                ],
            ],
        ]);

        $response = $geocoder->geocode('22201', fields: ['ffiec']);

        expect($response['results'][0]['_warnings'])
            ->toBe(['ffiec field was skipped since result is not street-level']);
    });

    it('preserves per-item warnings on a batch forward geocode', function (): void {
        $geocoder = geocoderReturning([
            'results' => [
                [
                    'query' => '1109 N Highland St, Arlington VA',
                    'response' => [
                        'results' => [
                            ['formatted_address' => '1109 N Highland St, Arlington, VA 22201'],
                        ],
                        '_warnings' => [
                            'The field congress is not recognized. Did you mean cd?',
                        ],
                    ],
                ],
                [
                    'query' => '525 University Ave, Toronto, ON, Canada',
                    'response' => [
                        'results' => [
                            ['formatted_address' => '525 University Ave, Toronto, ON M5G'],
                        ],
                        '_warnings' => [
                            'The field congress is not recognized. Did you mean cd?',
                        ],
                    ],
                ],
            ],
        ]);

        $response = $geocoder->geocode([
            '1109 N Highland St, Arlington VA',
            '525 University Ave, Toronto, ON, Canada',
        ], fields: ['congress']);

        expect($response['results'][0]['response']['_warnings'])
            ->toBe(['The field congress is not recognized. Did you mean cd?']);
        expect($response['results'][1]['response']['_warnings'])
            ->toBe(['The field congress is not recognized. Did you mean cd?']);
    });

    it('preserves per-item warnings on a batch reverse geocode', function (): void {
        $geocoder = geocoderReturning([
            'results' => [
                [
                    'query' => '35.9746000,-77.9658000',
                    'response' => [
                        'results' => [
                            ['formatted_address' => '101 W Washington St, Nashville, NC 27856'],
                        ],
                        '_warnings' => [
                            'There is a newer API version available, please consider upgrading to v2.',
                        ],
                    ],
                ],
            ],
        ]);

        $response = $geocoder->reverse(['35.9746000,-77.9658000']);

        expect($response['results'][0]['response']['_warnings'])
            ->toBe(['There is a newer API version available, please consider upgrading to v2.']);
    });

    it('does not invent a warnings key when the API sends none', function (): void {
        $geocoder = geocoderReturning([
            'results' => [
                ['formatted_address' => '1109 N Highland St, Arlington, VA 22201'],
            ],
        ]);

        $response = $geocoder->geocode('1109 N Highland St, Arlington VA');

        expect($response)->not->toHaveKey('_warnings');
        expect($response['_warnings'] ?? [])->toBe([]);
    });
});

describe('Lists responses', function (): void {
    it('preserves warnings when uploading an inline list', function (): void {
        $geocoder = geocoderReturning([
            'id' => 42,
            'file' => ['filename' => 'inline.csv'],
            'status' => ['state' => 'PROCESSING'],
            '_warnings' => [
                'The following field was not recognized and has been skipped: congressional_district',
            ],
        ]);

        $response = $geocoder->uploadInlineList(
            "address\n1109 N Highland St, Arlington VA",
            'inline.csv',
            GeocodeDirection::Forward,
            '{{A}}',
            fields: ['congressional_district'],
        );

        expect($response['_warnings'])
            ->toBe(['The following field was not recognized and has been skipped: congressional_district']);
    });

    it('preserves warnings on list status', function (): void {
        $geocoder = geocoderReturning([
            'id' => 42,
            'status' => ['state' => 'COMPLETED'],
            '_warnings' => [
                'The fields parameter should contain a comma-separated list of fields instead of an array',
            ],
        ]);

        expect($geocoder->listStatus(42)['_warnings'])
            ->toBe(['The fields parameter should contain a comma-separated list of fields instead of an array']);
    });

    it('preserves warnings when listing all lists', function (): void {
        $geocoder = geocoderReturning([
            'data' => [],
            'meta' => ['total' => 0],
            '_warnings' => [
                'The fields parameter should contain a comma-separated list of fields instead of an array',
            ],
        ]);

        expect($geocoder->lists()['_warnings'])
            ->toBe(['The fields parameter should contain a comma-separated list of fields instead of an array']);
    });
});

describe('Distance matrix job responses', function (): void {
    it('preserves warnings when creating a job', function (): void {
        $geocoder = geocoderReturning([
            'identifier' => 'dmj_abc123',
            'status' => 'PROCESSING',
            '_warnings' => [
                'There is a newer API version available, please consider upgrading to v2.',
            ],
        ]);

        $response = $geocoder->createDistanceMatrixJob(
            'Store coverage',
            [[38.886665, -77.094733]],
            [[38.897675, -77.036547]],
        );

        expect($response['_warnings'])
            ->toBe(['There is a newer API version available, please consider upgrading to v2.']);
    });

    it('preserves warnings on job status', function (): void {
        $geocoder = geocoderReturning([
            'data' => ['identifier' => 'dmj_abc123', 'status' => 'COMPLETED'],
            '_warnings' => [
                'The fields parameter should contain a comma-separated list of fields instead of an array',
            ],
        ]);

        expect($geocoder->distanceMatrixJobStatus('dmj_abc123')['_warnings'])
            ->toBe(['The fields parameter should contain a comma-separated list of fields instead of an array']);
    });

    it('preserves warnings when listing jobs', function (): void {
        $geocoder = geocoderReturning([
            'data' => [],
            'meta' => ['total' => 0],
            '_warnings' => [
                'The fields parameter should contain a comma-separated list of fields instead of an array',
            ],
        ]);

        expect($geocoder->distanceMatrixJobs()['_warnings'])
            ->toBe(['The fields parameter should contain a comma-separated list of fields instead of an array']);
    });
});

describe('Error responses', function (): void {
    it('exposes warnings attached to an error response', function (): void {
        $geocoder = new Geocodio(
            Client::create([
                new Response(422, ['Content-Type' => 'application/json'], json_encode([
                    'error' => 'Could not geocode address. Postal code or city required.',
                    '_warnings' => [
                        'The field congress is not recognized. Did you mean cd?',
                    ],
                ])),
            ])->client()
        );
        $geocoder->setApiKey('test-key');

        try {
            $geocoder->geocode('1109 N Highland St', fields: ['congress']);

            $this->fail('Expected a GeocodioException to be thrown');
        } catch (GeocodioException $e) {
            expect($e->getMessage())
                ->toBe('Request Error: Could not geocode address. Postal code or city required.');
            expect($e->warnings())
                ->toBe(['The field congress is not recognized. Did you mean cd?']);
        }
    });

    it('reports no warnings when the error response carries none', function (): void {
        $geocoder = new Geocodio(
            Client::create([
                new Response(403, ['Content-Type' => 'application/json'], json_encode([
                    'error' => 'Invalid API key',
                ])),
            ])->client()
        );
        $geocoder->setApiKey('BAD_API_KEY');

        try {
            $geocoder->geocode('1109 N Highland St, Arlington VA');

            $this->fail('Expected a GeocodioException to be thrown');
        } catch (GeocodioException $e) {
            expect($e->warnings())->toBe([]);
        }
    });
});
