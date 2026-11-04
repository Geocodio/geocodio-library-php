<?php

use Geocodio\Geocodio;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

function distanceQueryParameters(?string $country): array
{
    $query = null;

    $handlerStack = HandlerStack::create(new MockHandler([
        new Response(200, [], json_encode(['destinations' => []])),
    ]));
    $handlerStack->push(Middleware::tap(function ($request) use (&$query): void {
        parse_str((string) $request->getUri()->getQuery(), $query);
    }));

    (new Geocodio(new Client(['handler' => $handlerStack])))
        ->setApiKey('test-key')
        ->distance('1 Queen St', ['50 Cuba St'], country: $country);

    return $query;
}

it('sends the country on distance requests', function (): void {
    expect(distanceQueryParameters('NZ'))->toMatchArray([
        'origin' => '1 Queen St',
        'country' => 'NZ',
    ]);
});

it('leaves the country out of distance requests by default', function (): void {
    expect(distanceQueryParameters(null))->not->toHaveKey('country');
});
