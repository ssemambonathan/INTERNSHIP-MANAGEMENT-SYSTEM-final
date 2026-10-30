<?php

namespace App\Services;

use InvalidArgumentException;

class GeolocationService
{
    private const EARTH_RADIUS_METRES = 6371000;

    public function isValidCoordinate(float $latitude, float $longitude): bool
    {
        return $latitude >= -90 && $latitude <= 90
            && $longitude >= -180 && $longitude <= 180;
    }

    /**
     * Great-circle distance in metres using the Haversine formula.
     */
    public function distanceInMetres(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        if (! $this->isValidCoordinate($fromLat, $fromLng) || ! $this->isValidCoordinate($toLat, $toLng)) {
            throw new InvalidArgumentException('Invalid GPS coordinates supplied.');
        }

        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return round(2 * self::EARTH_RADIUS_METRES * asin(min(1, sqrt($a))), 2);
    }

    public function isWithinRadius(
        float $latitude,
        float $longitude,
        float $companyLatitude,
        float $companyLongitude,
        int $radiusMetres,
    ): bool {
        return $this->distanceInMetres($latitude, $longitude, $companyLatitude, $companyLongitude) <= $radiusMetres;
    }
}
