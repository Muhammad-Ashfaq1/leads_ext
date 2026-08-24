<?php

namespace Tests\Unit;

use App\Services\GeospatialGridService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeospatialGridServiceTest extends TestCase
{
    private GeospatialGridService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeospatialGridService();
    }

    public function test_is_coordinates_detects_valid_and_invalid_formats(): void
    {
        $this->assertTrue($this->service->isCoordinates('32.7767,-96.7970'));
        $this->assertTrue($this->service->isCoordinates('32.7767, -96.7970'));
        $this->assertTrue($this->service->isCoordinates('+40.7128, -74.0060'));
        $this->assertTrue($this->service->isCoordinates('-33.8688, 151.2093'));

        $this->assertFalse($this->service->isCoordinates('Dallas, TX'));
        $this->assertFalse($this->service->isCoordinates('90210'));
        $this->assertFalse($this->service->isCoordinates('New York'));
        $this->assertFalse($this->service->isCoordinates('999.999, 999.999'));
    }

    public function test_parse_coordinates_extracts_floats(): void
    {
        [$lat, $lng] = $this->service->parseCoordinates('32.7767, -96.7970');
        $this->assertEqualsWithDelta(32.7767, $lat, 0.0001);
        $this->assertEqualsWithDelta(-96.7970, $lng, 0.0001);

        [$latInvalid, $lngInvalid] = $this->service->parseCoordinates('invalid');
        $this->assertNull($latInvalid);
        $this->assertNull($lngInvalid);
    }

    public function test_bounds_from_point_computes_correct_deltas(): void
    {
        $bounds = $this->service->boundsFromPoint(32.7767, -96.7970, 10.0);

        $this->assertArrayHasKey('northeast', $bounds);
        $this->assertArrayHasKey('southwest', $bounds);

        $this->assertGreaterThan(32.7767, $bounds['northeast']['lat']);
        $this->assertLessThan(32.7767, $bounds['southwest']['lat']);
        $this->assertGreaterThan(-96.7970, $bounds['northeast']['lng']);
        $this->assertLessThan(-96.7970, $bounds['southwest']['lng']);
    }

    public function test_distance_km_computes_haversine_accurately(): void
    {
        // Dallas (32.7767, -96.7970) to Fort Worth (32.7555, -97.3308) is ~50 km
        $dist = $this->service->distanceKm(32.7767, -96.7970, 32.7555, -97.3308);
        $this->assertGreaterThan(45.0, $dist);
        $this->assertLessThan(55.0, $dist);
    }

    public function test_geocode_resolves_address_to_bounding_box(): void
    {
        Http::fake([
            'https://maps.googleapis.com/maps/api/geocode/json*' => Http::response([
                'status' => 'OK',
                'results' => [
                    [
                        'formatted_address' => 'Dallas, TX, USA',
                        'geometry' => [
                            'bounds' => [
                                'northeast' => ['lat' => 33.0237, 'lng' => -96.5368],
                                'southwest' => ['lat' => 32.6183, 'lng' => -97.0004],
                            ],
                            'location' => [
                                'lat' => 32.7767,
                                'lng' => -96.7970,
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->service->geocode('Dallas, TX', 'dummy-key');

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(33.0237, $result['northeast']['lat'], 0.001);
        $this->assertEqualsWithDelta(-97.0004, $result['southwest']['lng'], 0.001);
        $this->assertSame('Dallas, TX, USA', $result['formatted_address']);
    }

    public function test_geocode_handles_raw_coordinates_directly(): void
    {
        $result = $this->service->geocode('32.7767, -96.7970', 'dummy-key');

        $this->assertNotNull($result);
        $this->assertGreaterThan(32.7767, $result['northeast']['lat']);
        $this->assertLessThan(32.7767, $result['southwest']['lat']);
    }

    public function test_generate_grid_creates_correct_matrix(): void
    {
        $bounds = [
            'northeast' => ['lat' => 33.0, 'lng' => -96.5],
            'southwest' => ['lat' => 32.5, 'lng' => -97.0],
        ];

        $cells = $this->service->generateGrid($bounds, stepKm: 15.0, targetLimit: 100);

        $this->assertNotEmpty($cells);
        $this->assertGreaterThan(1, count($cells));

        foreach ($cells as $cell) {
            $this->assertArrayHasKey('low', $cell);
            $this->assertArrayHasKey('high', $cell);
            $this->assertArrayHasKey('latitude', $cell['low']);
            $this->assertArrayHasKey('longitude', $cell['low']);
            $this->assertArrayHasKey('latitude', $cell['high']);
            $this->assertArrayHasKey('longitude', $cell['high']);

            $this->assertLessThanOrEqual($bounds['northeast']['lat'] + 0.001, $cell['high']['latitude']);
            $this->assertGreaterThanOrEqual($bounds['southwest']['lat'] - 0.001, $cell['low']['latitude']);
            $this->assertLessThanOrEqual($bounds['northeast']['lng'] + 0.001, $cell['high']['longitude']);
            $this->assertGreaterThanOrEqual($bounds['southwest']['lng'] - 0.001, $cell['low']['longitude']);
        }
    }
}
