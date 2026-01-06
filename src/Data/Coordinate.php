<?php

declare(strict_types=1);

namespace Geocodio\Data;

use InvalidArgumentException;

/**
 * Represents a geographic coordinate with optional identifier.
 *
 * Used for distance calculations where you need to track
 * which origin/destination corresponds to which result.
 */
readonly class Coordinate implements \Stringable
{
    public function __construct(
        public float $lat,
        public float $lng,
        public ?string $id = null
    ) {
        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException("Latitude must be between -90 and 90, got {$lat}");
        }

        if ($lng < -180 || $lng > 180) {
            throw new InvalidArgumentException("Longitude must be between -180 and 180, got {$lng}");
        }
    }

    /**
     * Create from various input formats.
     *
     * Accepts:
     * - Coordinate instance (returns as-is)
     * - String: "lat,lng" or "lat,lng,id"
     * - Array: [lat, lng] or [lat, lng, id]
     */
    public static function from(self|string|array $input): self
    {
        if ($input instanceof self) {
            return $input;
        }

        if (is_string($input)) {
            return self::fromString($input);
        }

        return self::fromArray($input);
    }

    /**
     * Create from string format "lat,lng" or "lat,lng,id".
     */
    public static function fromString(string $input): self
    {
        $parts = explode(',', $input);

        if (count($parts) < 2) {
            throw new InvalidArgumentException(
                "Invalid coordinate string format. Expected 'lat,lng' or 'lat,lng,id', got '{$input}'"
            );
        }

        $lat = trim($parts[0]);
        $lng = trim($parts[1]);
        $id = isset($parts[2]) ? trim($parts[2]) : null;

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            throw new InvalidArgumentException(
                "Coordinate values must be numeric. Got lat='{$lat}', lng='{$lng}'"
            );
        }

        return new self((float) $lat, (float) $lng, $id);
    }

    /**
     * Create from array format [lat, lng] or [lat, lng, id].
     */
    public static function fromArray(array $input): self
    {
        if (count($input) < 2) {
            throw new InvalidArgumentException(
                'Invalid coordinate array. Expected [lat, lng] or [lat, lng, id]'
            );
        }

        $lat = $input[0];
        $lng = $input[1];
        $id = $input[2] ?? null;

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            throw new InvalidArgumentException(
                "Coordinate values must be numeric. Got lat='{$lat}', lng='{$lng}'"
            );
        }

        if ($id !== null && ! is_string($id) && ! is_numeric($id)) {
            throw new InvalidArgumentException(
                'Coordinate ID must be a string or numeric value'
            );
        }

        return new self((float) $lat, (float) $lng, $id !== null ? (string) $id : null);
    }

    /**
     * Convert to API object format.
     *
     * @return array{lat: float, lng: float, id?: string}
     */
    public function toArray(): array
    {
        $result = [
            'lat' => $this->lat,
            'lng' => $this->lng,
        ];

        if ($this->id !== null) {
            $result['id'] = $this->id;
        }

        return $result;
    }

    /**
     * Convert to string format "lat,lng" or "lat,lng,id".
     */
    public function toString(): string
    {
        $str = "{$this->lat},{$this->lng}";

        if ($this->id !== null) {
            $str .= ",{$this->id}";
        }

        return $str;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
