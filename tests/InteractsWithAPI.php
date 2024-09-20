<?php

namespace Geocodio\Tests;

use InvalidArgumentException;

trait InteractsWithAPI
{
    protected function getApiKeyFromEnvironment(): string
    {
        $apiKey = getenv('GEOCODIO_API_KEY');

        if (! $apiKey) {
            throw new InvalidArgumentException('Please specify Geocodio API key using the "GEOCODIO_API_KEY" environment variable');
        }

        return $apiKey;
    }

    protected function getHostnameFromEnvironment(): ?string
    {
        return getenv('GEOCODIO_HOSTNAME') ?? null;
    }
}
