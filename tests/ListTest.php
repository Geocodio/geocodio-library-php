<?php

declare(strict_types=1);

namespace Geocodio\Tests;

use Geocodio\Enums\GeocodeDirection;
use Geocodio\Exceptions\GeocodioException;
use Geocodio\Geocodio;
use Geocodio\Tests\TestDoubles\Client;
use Geocodio\Tests\TestDoubles\TestResponse;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

describe('Requests', function (): void {
    it('uploads a list', function (): void {
        $http = Client::create([
            TestResponse::successJson(),
        ]);

        $geocodio = (new Geocodio($http->client()));

        $geocodio->uploadList(
            __DIR__.'/Fixtures/simple.csv',
            GeocodeDirection::Forward,
            '{{B}} {{C}} {{D}} {{E}}'
        );

        $history = $http->history();

        expect($history)->toHaveCount(1);

        /** @var Request $request */
        $request = $history[0]['request'];
        $body = (string) $request->getBody();

        // Assert the API key is being sent
        parse_str($request->getUri()->getQuery(), $query);
        expect($query['api_key'])->toBeString();

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
            TestResponse::successJson(),
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

        // Assert the API key is being sent
        parse_str((string) $request->getUri()->getQuery(), $query);
        expect($query['api_key'])->toBeString();

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
            TestResponse::successJson(),
        ]);

        $geocodio = (new Geocodio($http->client()));

        $geocodio->lists();

        expect($http->history())->toHaveCount(1);

        /** @var Request */
        $request = $http->history()[0]['request'];

        // Assert the API key is being sent
        parse_str($request->getUri()->getQuery(), $query);
        expect($query['api_key'])->toBeString();

        // Assert path and method
        expect($request->getUri()->getPath())
            ->toBe(sprintf('/%s/lists', $geocodio->apiVersion()));
        expect($request->getMethod())->toBe('GET');

        // Assert nothing is sent in the body
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

        // Assert the API key is being sent
        parse_str($request->getUri()->getQuery(), $query);
        expect($query['api_key'])->toBeString();

        // Assert path and method
        expect($request->getMethod())->toBe('DELETE');
        expect($request->getUri()->getPath())
            ->toBe(sprintf('/%s/lists/11950669', $geocodio->apiVersion()));

        // Assert nothing is sent in the body
        expect((string) $request->getBody())->toBeEmpty();
    });

    it('can fetch list status', function (): void {
        $http = Client::create([
            TestResponse::successJson(),
        ]);

        $geocodio = (new Geocodio($http->client()));

        $geocodio->listStatus(11950669);

        /** @var Request */
        $request = $http->history()[0]['request'];

        // Assert the API key is being sent
        parse_str($request->getUri()->getQuery(), $query);
        expect($query['api_key'])->toBeString();

        // Assert method and path
        expect($request->getMethod())->toBe('GET');
        expect($request->getUri()->getPath())
            ->toBe(sprintf('/%s/lists/11950669', $geocodio->apiVersion()));

        // Assert no body is sent
        expect((string) $request->getBody())->toBeEmpty();
    });

    it('can download a list to a location', function (): void {
        $temp = tmpfile();
        $path = stream_get_meta_data($temp)['uri'];
        fclose($temp);

        $contents = file_get_contents(__DIR__.'/Fixtures/download.csv');

        $http = Client::create([
            new Response(200, [], $contents),
        ]);

        $geocodio = (new Geocodio($http->client()));

        $geocodio->downloadList(11951418, $path);

        $history = $http->history();

        expect($history)->toHaveCount(1);

        /** @var Request $request */
        $request = $history[0]['request'];

        // Assert the API key is being sent
        parse_str($request->getUri()->getQuery(), $query);
        expect($query['api_key'])->toBeString();

        // Assert file contents
        expect($path)->toBeFile();
        expect(file_get_contents($path))->toBe($contents);

        // Cleanup temp file
        unlink($path);
    });
});

describe('Exception handling', function (): void {
    it('throws exception when uploading from invalid file', function (): void {
        $http = Client::create([
            TestResponse::invalidData(),
        ]);

        $geocodio = (new Geocodio($http->client()));

        $geocodio->uploadList(
            __DIR__.'/Fixtures/invalid.csv',
            GeocodeDirection::Forward,
            '{{B}} {{C}} {{D}} {{E}}'
        );
    })->throws(
        GeocodioException::class,
        'Request Error: Uploaded spreadsheet appears to be empty or unreadable'
    );

    it('throws exception when uploading from invalid inline file', function (): void {
        $http = Client::create([
            TestResponse::invalidData(),
        ]);

        $geocodio = (new Geocodio($http->client()));

        $csvData = <<<'CSV'
    name,street,city,state,zip
    CSV;

        $geocodio->uploadInlineList(
            $csvData,
            'coffee-shopts.csv',
            GeocodeDirection::Forward,
            '{{B}} {{C}} {{D}} {{E}}'
        );
    })->throws(
        GeocodioException::class,
        'Request Error: Uploaded spreadsheet appears to be empty or unreadable'
    );

    it('throws an exception if the file doesn\'t exist', function (): void {
        (new Geocodio)
            ->uploadList(
                __DIR__.'/Fixtures/not-found.csv',
                GeocodeDirection::Forward,
                '{{B}} {{C}} {{D}} {{E}}'
            );
    })->throws(GeocodioException::class, sprintf(
        'File (%s/Fixtures/not-found.csv) not found',
        __DIR__,
    ));
});
