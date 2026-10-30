<?php

namespace Tests\Unit;

use App\Services\GeolocationService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class GeolocationServiceTest extends TestCase
{
    private GeolocationService $geo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->geo = new GeolocationService;
    }

    public function test_identical_coordinates_have_zero_distance(): void
    {
        $this->assertSame(0.0, $this->geo->distanceInMetres(0.347596, 32.582520, 0.347596, 32.582520));
    }

    public function test_known_distance_between_kampala_and_entebbe(): void
    {
        // Kampala (0.347596, 32.582520) to Entebbe (0.051184, 32.463708) is about 35.5 km.
        $distance = $this->geo->distanceInMetres(0.347596, 32.582520, 0.051184, 32.463708);

        $this->assertEqualsWithDelta(35500, $distance, 1000);
    }

    public function test_one_degree_of_latitude_is_about_111_km(): void
    {
        $distance = $this->geo->distanceInMetres(0, 0, 1, 0);

        $this->assertEqualsWithDelta(111195, $distance, 1);
    }

    public function test_radius_boundary_is_inclusive(): void
    {
        // A point 0.001 degrees north of the company is roughly 111 m away.
        $this->assertTrue($this->geo->isWithinRadius(0.348596, 32.582520, 0.347596, 32.582520, 150));
        $this->assertFalse($this->geo->isWithinRadius(0.349596, 32.582520, 0.347596, 32.582520, 150));
    }

    public function test_invalid_latitude_or_longitude_is_rejected(): void
    {
        $this->assertFalse($this->geo->isValidCoordinate(90.1, 0));
        $this->assertFalse($this->geo->isValidCoordinate(0, -180.1));
        $this->assertFalse($this->geo->isValidCoordinate(-90.5, 180.5));
        $this->assertTrue($this->geo->isValidCoordinate(90, 180));
        $this->assertTrue($this->geo->isValidCoordinate(-90, -180));
    }

    public function test_distance_throws_for_invalid_coordinates(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->geo->distanceInMetres(91, 0, 0, 0);
    }
}
