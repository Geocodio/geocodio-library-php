<?php

declare(strict_types=1);

namespace Geocodio\Tests;

use Geocodio\Enums\DistanceCalculationType;
use Geocodio\Geocodio;
use Geocodio\Tests\TestDoubles\Client;
use GuzzleHttp\Psr7\Response;

/**
 * Decode the JSON body of the only request sent through the mock client.
 */
function sentJsonBody(Client $http): array
{
    expect($http->history())->toHaveCount(1);

    return json_decode((string) $http->history()[0]['request']->getBody(), true);
}

beforeEach(function (): void {
    $this->http = Client::create([
        new Response(200, ['Content-Type' => 'application/json'], json_encode(['results' => []])),
    ]);

    $this->geocoder = new Geocodio($this->http->client());
    $this->geocoder->setApiKey('test-key');
});

describe('POST /distance-matrix calculation_type', function (): void {
    it('sends calculation_type when given an enum', function (): void {
        $this->geocoder->distanceMatrix(
            ['38.8977,-77.0365,home', '38.886672,-77.094735,office'],
            ['38.9072,-77.0369,capitol', '38.8814,-77.0916,pentagon'],
            calculationType: DistanceCalculationType::Pairs
        );

        expect(sentJsonBody($this->http))->toMatchArray(['calculation_type' => 'pairs']);
    });

    it('sends calculation_type when given a string', function (): void {
        $this->geocoder->distanceMatrix(
            ['38.8977,-77.0365'],
            ['38.9072,-77.0369'],
            calculationType: 'matrix'
        );

        expect(sentJsonBody($this->http))->toMatchArray(['calculation_type' => 'matrix']);
    });

    it('omits calculation_type when not given', function (): void {
        $this->geocoder->distanceMatrix(['38.8977,-77.0365'], ['38.9072,-77.0369']);

        expect(sentJsonBody($this->http))->not->toHaveKey('calculation_type');
    });
});

describe('POST /distance-jobs calculation_type', function (): void {
    it('sends calculation_type when creating a job', function (): void {
        $this->geocoder->createDistanceMatrixJob(
            'Commutes',
            ['38.8977,-77.0365,home', '38.886672,-77.094735,office'],
            ['38.9072,-77.0369,capitol', '38.8814,-77.0916,pentagon'],
            calculationType: DistanceCalculationType::Pairs
        );

        $request = $this->http->history()[0]['request'];
        expect($request->getMethod())->toBe('POST');
        expect($request->getUri()->getPath())->toEndWith('/distance-jobs');
        expect(sentJsonBody($this->http))->toMatchArray(['calculation_type' => 'pairs']);
    });

    it('omits calculation_type when creating a job without it', function (): void {
        $this->geocoder->createDistanceMatrixJob('Commutes', ['38.8977,-77.0365'], ['38.9072,-77.0369']);

        expect(sentJsonBody($this->http))->not->toHaveKey('calculation_type');
    });
});
