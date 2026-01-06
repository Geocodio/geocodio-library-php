<?php

declare(strict_types=1);

namespace Geocodio\Enums;

enum DistanceMode: string
{
    case Driving = 'driving';
    case Straightline = 'straightline';
    case Haversine = 'haversine'; // Alias for straightline
}
