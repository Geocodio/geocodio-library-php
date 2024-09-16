<?php

declare(strict_types=1);

namespace Geocodio\Tests\TestDoubles;

use GuzzleHttp\Psr7\Response;

class TestResponse
{
    public static function successJson(): Response
    {
        return new Response(200, body: json_encode([]));
    }

    public static function invalidData(): Response
    {
        return new Response(
            400,
            body: json_encode([
                'error' => 'Uploaded spreadsheet appears to be empty or unreadable',
            ])
        );
    }
}
