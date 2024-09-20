<?php

declare(strict_types=1);

namespace Geocodio\Tests\TestDoubles;

use GuzzleHttp\Client as Http;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

class Client
{
    protected MockHandler $mockHandler;

    protected array $history = [];

    protected HandlerStack $handlerStack;

    public function __construct()
    {
        $this->mockHandler = new MockHandler;
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->handlerStack->push(Middleware::history($this->history));
    }

    /**
     * @param  array<int, Response>  $responses
     */
    public static function create($responses = []): Client
    {
        $client = new Client;

        array_map(function (Response $response) use ($client): void {
            $client->withResponse($response);
        }, $responses);

        return $client;
    }

    public function client(): Http
    {
        return new Http(['handler' => $this->handlerStack]);
    }

    public function withResponse(Response $response): self
    {
        $this->mockHandler->append($response);

        return $this;
    }

    /**
     * @return array<int, array{request: Request, response: Response}>
     */
    public function history(): array
    {
        return $this->history;
    }
}
