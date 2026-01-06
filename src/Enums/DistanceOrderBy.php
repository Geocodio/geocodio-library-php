<?php

declare(strict_types=1);

namespace Geocodio\Enums;

enum DistanceOrderBy: string
{
    case Distance = 'distance';
    case Duration = 'duration';
}
