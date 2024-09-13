<?php

declare(strict_types=1);

namespace Geocodio\Tests;

use Geocodio\Enums\GeocodeDirection;
use Geocodio\Exceptions\GeocodioException;
use Geocodio\Geocodio;
use Geocodio\Tests\TestDoubles\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

it('throws an exception if the file doesn\'t exist', function (): void {
    $response = (new Geocodio)
        ->uploadList(
            __DIR__.'/Fixtures/not-found.csv',
            GeocodeDirection::Forward,
            '{{B}} {{C}} {{D}} {{E}}'
        );

    ray('uploadList', $response);
})->throws(GeocodioException::class, sprintf(
    'File (%s/Fixtures/not-found.csv) not found',
    __DIR__,
));

it('uploads a list', function (): void {
    $http = Client::create([
        new Response(200, body: json_encode([])),
    ]);

    $geocodio = (new Geocodio($http->client()));

    $geocodio->uploadList(
        __DIR__.'/Fixtures/simple.csv',
        GeocodeDirection::Forward,
        '{{B}} {{C}} {{D}} {{E}}'
    );

    $history = $http->history();

    expect($history)->toHaveCount(1);

    $request = $history[0]['request'];
    $body = (string) $request->getBody();

    // Assert that the request is a POST request
    expect($request->getMethod())->toBe('POST');
    expect($request->getUri()->getPath())
        ->toBe(sprintf('/%s/lists', $geocodio->apiVersion()));

    // Assert that the Content-Type header is set correctly
    expect($request->getHeaderLine('Content-Type'))->toContain('multipart/form-data');

    // Assert that the body contains the expected multi-part form data
    expect($body)->toContain('name="file"');
    expect($body)->toContain('name="direction"');
    expect($body)->toContain('name="format"');
    expect($body)->toContain('name="callback"');

    // Assert specific values
    expect($body)->toContain('filename="simple.csv"');
    expect($body)->toContain('forward'); // The value of GeocodeDirection::Forward
    expect($body)->toContain('{{B}} {{C}} {{D}} {{E}}');
});

it('uploads an inline list', function (): void {
    $http = Client::create([
        new Response(200, body: json_encode([])),
    ]);

    $csvData = <<<'CSV'
    name,street,city,state,zip
    "Peregrine Espresso","660 Pennsylvania Ave SE",Washington,DC,20003
    "Lot 38 Espresso Bar","1001 2nd St SE",Washington,DC,20003
    CSV;

    $geocodio = (new Geocodio($http->client()));

    $geocodio->uploadInlineList(
        $csvData,
        'coffee-shops.csv',
        GeocodeDirection::Forward,
        '{{B}} {{C}} {{D}} {{E}}'
    );

    $history = $http->history();

    expect($history)->toHaveCount(1);

    $request = $history[0]['request'];
    $body = (string) $request->getBody();

    // Assert that the request is a POST request
    expect($request->getMethod())->toBe('POST');
    expect($request->getUri()->getPath())
        ->toBe(sprintf('/%s/lists', $geocodio->apiVersion()));

    // Assert that the Content-Type header is set correctly
    expect($request->getHeaderLine('Content-Type'))
        ->toContain('multipart/form-data');

    // Assert that the body contains the expected multi-part form data
    expect($body)->toContain('name="file"');
    expect($body)->toContain('name="direction"');
    expect($body)->toContain('name="format"');
    expect($body)->toContain('name="callback"');

    // Assert specific values
    expect($body)->toContain('filename="coffee-shops.csv"');
    expect($body)->toContain('name,street,city,state,zip');
    expect($body)->toContain(
        '"Peregrine Espresso","660 Pennsylvania Ave SE",Washington,DC,20003'
    );
    expect($body)->toContain(
        '"Lot 38 Espresso Bar","1001 2nd St SE",Washington,DC,20003'
    );
    // The value of GeocodeDirection::Forward
    expect($body)->toContain('forward');
    expect($body)->toContain('{{B}} {{C}} {{D}} {{E}}');
});

it('can fetch your lists', function (): void {
    $http = Client::create([
        new Response(200, body: json_encode([])),
    ]);

    $geocodio = (new Geocodio($http->client()));

    $geocodio->lists();

    expect($http->history())->toHaveCount(1);

    /** @var Request */
    $request = $http->history()[0]['request'];

    expect($request->getMethod())->toBe('GET');
    expect($request->getUri()->getPath())
        ->toBe(sprintf('/%s/lists', $geocodio->apiVersion()));
    expect((string) $request->getBody())->toBeEmpty();
});

it('can delete lists', function (): void {
    $http = Client::create([
        new Response(200, body: json_encode([])),
    ]);

    $geocodio = (new Geocodio($http->client()));

    $geocodio->deleteList(11950669);

    /** @var Request */
    $request = $http->history()[0]['request'];

    expect($request->getMethod())->toBe('DELETE');
    expect($request->getUri()->getPath())
        ->toBe(sprintf('/%s/lists/11950669', $geocodio->apiVersion()));
    expect((string) $request->getBody())->toBeEmpty();
});

it('can fetch list status', function (): void {
    $http = Client::create([
        new Response(200, body: json_encode([])),
    ]);

    $geocodio = (new Geocodio($http->client()));

    $geocodio->listStatus(11950669);

    /** @var Request */
    $request = $http->history()[0]['request'];

    expect($request->getMethod())->toBe('GET');
    expect($request->getUri()->getPath())
        ->toBe(sprintf('/%s/lists/11950669', $geocodio->apiVersion()));
    expect((string) $request->getBody())->toBeEmpty();
});

it('throws exception when uploading from invalid file', function (): void {
    $this->markTestIncomplete();
});
