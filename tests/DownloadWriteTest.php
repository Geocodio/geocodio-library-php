<?php

use Geocodio\Geocodio;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class RejectingDownloadStream
{
    public mixed $context;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        return true;
    }

    public function stream_write(string $data): int
    {
        return 0;
    }
}

it('throws when the download destination rejects a write', function (string $method, int|string $identifier): void {
    $handler = new MockHandler([new Response(200, [], "address\n123 Main St\n")]);
    $geocodio = new Geocodio(new Client(['handler' => HandlerStack::create($handler)]));
    $geocodio->setApiKey('test-key');

    stream_wrapper_register('geocodio-reject-write', RejectingDownloadStream::class);
    $path = 'geocodio-reject-write://download.csv';

    try {
        expect(fn () => $geocodio->{$method}($identifier, $path))
            ->toThrow(Exception::class, "Unable to write to file: {$path}");
    } finally {
        stream_wrapper_unregister('geocodio-reject-write');
    }
})->with([
    'list' => ['downloadList', 123],
    'distance matrix' => ['downloadDistanceMatrixJob', 'job-123'],
]);
