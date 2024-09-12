<?php

namespace Geocodio\Tests;

describe('Arch presets', function (): void {
    arch('security preset')->preset()->security();
    arch('php preset')->preset()->php();
});

describe('Custom relaxed preset', function (): void {
    arch('No final classes')
        ->expect('EchoLabs\Sparkle')
        ->classes()
        ->not
        ->toBeFinal();
});
