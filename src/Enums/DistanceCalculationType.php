<?php

declare(strict_types=1);

namespace Geocodio\Enums;

enum DistanceCalculationType: string
{
    case Matrix = 'matrix'; // Every origin measured against every destination
    case Pairs = 'pairs'; // Origin i measured against destination i only
}
