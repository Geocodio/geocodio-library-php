<?php

declare(strict_types=1);

namespace Geocodio\Enums;

enum GeocodeDirection: string
{
    case Forward = 'forward';
    case Reverse = 'reverse';
}
