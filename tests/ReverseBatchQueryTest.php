<?php

use Geocodio\Geocodio;
use Geocodio\Tests\TestDoubles\Client;
use GuzzleHttp\Psr7\Response;

it('sends batch reverse coordinates only in the request body', function (array $query): void {
    $http = Client::create([new Response(200, [], '{}')]);
    $geocoder = (new Geocodio($http->client()))->setApiKey('test-key');

    $geocoder->reverse($query, ['timezone']);

    $request = $http->history()[0]['request'];
    parse_str((string) $request->getUri()->getQuery(), $params);

    expect($request->getMethod())->toBe('POST')
        ->and($params)->not->toHaveKey('q')
        ->and($params)->toMatchArray(['fields' => 'timezone'])
        ->and(json_decode((string) $request->getBody(), true))->toBe(['35.9746,-77.9658', '32.8793,-96.6303']);
})->with([
    'strings' => [['35.9746,-77.9658', '32.8793,-96.6303']],
    'arrays' => [[[35.9746, -77.9658], [32.8793, -96.6303]]],
]);

it('sends a single reverse coordinate in the query string', function (string|array $query): void {
    $http = Client::create([new Response(200, [], '{}')]);
    $geocoder = (new Geocodio($http->client()))->setApiKey('test-key');

    $geocoder->reverse($query);

    $request = $http->history()[0]['request'];
    parse_str((string) $request->getUri()->getQuery(), $params);

    expect($request->getMethod())->toBe('GET')
        ->and($params)->toMatchArray(['q' => '35.9746,-77.9658']);
})->with([
    'string' => ['35.9746,-77.9658'],
    'array' => [[35.9746, -77.9658]],
]);
