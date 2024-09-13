<?php

declare(strict_types=1);

namespace Geocodio\Tests;

use Exception;
use Geocodio\Enums\GeocodeDirection;
use Geocodio\Geocodio;
use Geocodio\Tests\TestDoubles\Client;
use GuzzleHttp\Psr7\Response;

it('throws an exception if the file doesn\'t exist', function (): void {
    $response = (new Geocodio)
        ->uploadList(
            __DIR__.'/Fixtures/not-found.csv',
            GeocodeDirection::Forward,
            '{{B}} {{C}} {{D}} {{E}}'
        );

    ray('uploadList', $response);
})->throws(Exception::class, 'File ('.__DIR__.'/Fixtures/not-found.csv) not found');

it('uploads a list', function (): void {
    $client = Client::create([
        new Response(200, body: json_encode([])),
    ]);

    (new Geocodio($client->client()))
        ->uploadList(
            __DIR__.'/Fixtures/simple.csv',
            GeocodeDirection::Forward,
            '{{B}} {{C}} {{D}} {{E}}'
        );

    $history = $client->history();

    expect($history)->toHaveCount(1);

    $request = $history[0]['request'];
    $body = (string) $request->getBody();

    // Assert that the request is a POST request
    expect($request->getMethod())->toBe('POST');

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
    $client = Client::create([
        new Response(200, body: json_encode([])),
    ]);

    $csvData = <<<'CSV'
    name,street,city,state,zip
    "Peregrine Espresso","660 Pennsylvania Ave SE",Washington,DC,20003
    "Lot 38 Espresso Bar","1001 2nd St SE",Washington,DC,20003
    CSV;

    (new Geocodio($client->client()))
        ->uploadInlineList(
            $csvData,
            'coffee-shops.csv',
            GeocodeDirection::Forward,
            '{{B}} {{C}} {{D}} {{E}}'
        );

    $history = $client->history();

    expect($history)->toHaveCount(1);

    $request = $history[0]['request'];
    $body = (string) $request->getBody();

    // Assert that the request is a POST request
    expect($request->getMethod())->toBe('POST');

    // Assert that the Content-Type header is set correctly
    expect($request->getHeaderLine('Content-Type'))->toContain('multipart/form-data');

    // Assert that the body contains the expected multi-part form data
    expect($body)->toContain('name="file"');
    expect($body)->toContain('name="direction"');
    expect($body)->toContain('name="format"');
    expect($body)->toContain('name="callback"');

    // Assert specific values
    expect($body)->toContain('filename="coffee-shops.csv"');
    expect($body)->toContain('name,street,city,state,zip');
    expect($body)->toContain('"Peregrine Espresso","660 Pennsylvania Ave SE",Washington,DC,20003');
    expect($body)->toContain('"Lot 38 Espresso Bar","1001 2nd St SE",Washington,DC,20003');
    expect($body)->toContain('forward'); // The value of GeocodeDirection::Forward
    expect($body)->toContain('{{B}} {{C}} {{D}} {{E}}');
});

it('can fetch your lists', function (): void {
    $this->markTestSkipped('Need to mock the call');

    $response = $this->geocoder->lists();

    ray('lists', $response);
});

it('can delete lists', function (): void {
    $this->markTestSkipped('Need to mock the call');

    $response = $this->geocoder->lists();

    foreach ($response['data'] as $list) {
        $response = $this->geocoder->deleteList($list['id']);
        ray('deleteList', $response);
    }
});

it('can fetch list status', function (): void {
    $this->markTestSkipped('Need to mock the call');

    $response = $this->geocoder->listStatus(11950669);

    ray('listStatus', $response);
});

it('throws exception when uploading from invalid file', function (): void {
    $this->markTestIncomplete();
});
