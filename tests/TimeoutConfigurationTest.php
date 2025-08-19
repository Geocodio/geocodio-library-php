<?php

use Geocodio\Geocodio;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;

it('applies custom single timeout to single geocode requests', function (): void {
    $capturedOptions = null;

    $mockHandler = new MockHandler([
        new Response(200, [], json_encode(['results' => []])),
    ]);

    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::tap(function ($request, $options) use (&$capturedOptions): void {
        $capturedOptions = $options;
    }));

    $client = new Client(['handler' => $handlerStack]);
    $geocodio = new Geocodio($client);

    $geocodio->setApiKey('test-key')
        ->setSingleTimeoutMs(15000);

    $geocodio->geocode('123 Main St');

    expect($capturedOptions)->toHaveKey(RequestOptions::TIMEOUT);
    expect($capturedOptions[RequestOptions::TIMEOUT])->toEqual(15); // 15000ms = 15s
});

it('applies custom batch timeout to batch geocode requests', function (): void {
    $capturedOptions = null;

    $mockHandler = new MockHandler([
        new Response(200, [], json_encode(['results' => []])),
    ]);

    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::tap(function ($request, $options) use (&$capturedOptions): void {
        $capturedOptions = $options;
    }));

    $client = new Client(['handler' => $handlerStack]);
    $geocodio = new Geocodio($client);

    $geocodio->setApiKey('test-key')
        ->setBatchTimeoutMs(7200000); // 2 hours

    $geocodio->geocode(['123 Main St', '456 Oak Ave']);

    expect($capturedOptions)->toHaveKey(RequestOptions::TIMEOUT);
    expect($capturedOptions[RequestOptions::TIMEOUT])->toEqual(7200); // 7200000ms = 7200s
});

it('applies custom single timeout to single reverse geocode requests', function (): void {
    $capturedOptions = null;

    $mockHandler = new MockHandler([
        new Response(200, [], json_encode(['results' => []])),
    ]);

    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::tap(function ($request, $options) use (&$capturedOptions): void {
        $capturedOptions = $options;
    }));

    $client = new Client(['handler' => $handlerStack]);
    $geocodio = new Geocodio($client);

    $geocodio->setApiKey('test-key')
        ->setSingleTimeoutMs(20000); // 20 seconds

    $geocodio->reverse('38.9,-77.0');

    expect($capturedOptions)->toHaveKey(RequestOptions::TIMEOUT);
    expect($capturedOptions[RequestOptions::TIMEOUT])->toEqual(20);
});

it('applies custom batch timeout to batch reverse geocode requests', function (): void {
    $capturedOptions = null;

    $mockHandler = new MockHandler([
        new Response(200, [], json_encode(['results' => []])),
    ]);

    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::tap(function ($request, $options) use (&$capturedOptions): void {
        $capturedOptions = $options;
    }));

    $client = new Client(['handler' => $handlerStack]);
    $geocodio = new Geocodio($client);

    $geocodio->setApiKey('test-key')
        ->setBatchTimeoutMs(3600000); // 1 hour

    $geocodio->reverse(['38.9,-77.0', '40.7,-74.0']);

    expect($capturedOptions)->toHaveKey(RequestOptions::TIMEOUT);
    expect($capturedOptions[RequestOptions::TIMEOUT])->toEqual(3600);
});

it('applies custom lists timeout to list operations', function (): void {
    $capturedOptions = null;

    $mockHandler = new MockHandler([
        new Response(200, [], json_encode(['id' => 1])),
    ]);

    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::tap(function ($request, $options) use (&$capturedOptions): void {
        $capturedOptions = $options;
    }));

    $client = new Client(['handler' => $handlerStack]);
    $geocodio = new Geocodio($client);

    $geocodio->setApiKey('test-key')
        ->setListsTimeoutMs(180000); // 3 minutes

    $geocodio->lists();

    expect($capturedOptions)->toHaveKey(RequestOptions::TIMEOUT);
    expect($capturedOptions[RequestOptions::TIMEOUT])->toEqual(180);
});

it('applies default timeout values when not customized', function (): void {
    $capturedOptions = null;

    $mockHandler = new MockHandler([
        new Response(200, [], json_encode(['results' => []])),
    ]);

    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::tap(function ($request, $options) use (&$capturedOptions): void {
        $capturedOptions = $options;
    }));

    $client = new Client(['handler' => $handlerStack]);
    $geocodio = new Geocodio($client);

    $geocodio->setApiKey('test-key');

    // Test single geocode uses default 5 seconds
    $geocodio->geocode('123 Main St');
    expect($capturedOptions[RequestOptions::TIMEOUT])->toEqual(5);
});

it('converts milliseconds to seconds for Guzzle', function (): void {
    $capturedOptions = null;

    $mockHandler = new MockHandler([
        new Response(200, [], json_encode(['results' => []])),
    ]);

    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::tap(function ($request, $options) use (&$capturedOptions): void {
        $capturedOptions = $options;
    }));

    $client = new Client(['handler' => $handlerStack]);
    $geocodio = new Geocodio($client);

    $geocodio->setApiKey('test-key')
        ->setSingleTimeoutMs(2500); // 2.5 seconds

    $geocodio->geocode('123 Main St');

    expect($capturedOptions[RequestOptions::TIMEOUT])->toEqual(2.5);
});
