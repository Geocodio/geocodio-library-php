<?php

use Geocodio\Enums\DistanceOrderBy;
use Geocodio\Enums\DistanceSortOrder;
use Geocodio\Geocodio;
use Geocodio\Tests\TestDoubles\Client;
use GuzzleHttp\Psr7\Response;

it('sends sorting parameters independently of filters', function (
    string $method,
    ?int $maxResults,
    string|DistanceOrderBy $orderBy,
    string|DistanceSortOrder $sortOrder
): void {
    $http = Client::create([new Response(200, [], '{}')]);
    $geocoder = (new Geocodio($http->client()))->setApiKey('test-key');

    $args = match ($method) {
        'distance' => ['38,-78', ['39,-77']],
        'distanceMatrix' => [['38,-78'], ['39,-77']],
        'createDistanceMatrixJob' => ['sort-test', ['38,-78'], ['39,-77']],
    };

    $geocoder->$method(...[
        ...$args,
        'mode' => 'driving',
        'maxResults' => $maxResults,
        'orderBy' => $orderBy,
        'sortOrder' => $sortOrder,
    ]);

    $request = $http->history()[0]['request'];
    if ($method === 'distance') {
        parse_str($request->getUri()->getQuery(), $params);
    } else {
        $params = json_decode((string) $request->getBody(), true);
    }

    expect($params)->toMatchArray([
        'order_by' => 'duration',
        'sort_order' => 'desc',
    ]);
})->with(['distance', 'distanceMatrix', 'createDistanceMatrixJob'])
    ->with([null, 2])
    ->with([
        'strings' => ['duration', 'desc'],
        'enums' => [DistanceOrderBy::Duration, DistanceSortOrder::Desc],
    ]);
