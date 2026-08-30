<?php

namespace Tests\Unit;

use App\Services\GooglePlacesService;
use Tests\TestCase;

class GooglePlacesLocationFilterTest extends TestCase
{
    private GooglePlacesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(GooglePlacesService::class);
    }

    public function test_keeps_places_in_the_selected_city(): void
    {
        $this->assertTrue($this->service->placeMatchesTargetLocation([
            'displayName' => ['text' => 'Windy City Plumbing'],
            'formattedAddress' => '123 N Wells St, Chicago, IL 60654, USA',
        ], 'Chicago, IL', null));
    }

    public function test_drops_places_from_a_different_city(): void
    {
        $this->assertFalse($this->service->placeMatchesTargetLocation([
            'displayName' => ['text' => 'Nearby Me Plumbing'],
            'formattedAddress' => 'Main Boulevard, Gulberg, Lahore, Pakistan',
            'location' => ['latitude' => 31.52, 'longitude' => 74.35],
        ], 'Chicago, IL', null));
    }

    public function test_drops_places_outside_geocoded_bounds(): void
    {
        $bounds = [
            'northeast' => ['lat' => 42.02, 'lng' => -87.52],
            'southwest' => ['lat' => 41.64, 'lng' => -87.94],
        ];

        $this->assertFalse($this->service->placeMatchesTargetLocation([
            'formattedAddress' => 'Lahore, Pakistan',
            'location' => ['latitude' => 31.52, 'longitude' => 74.35],
        ], 'Chicago, IL', $bounds));

        $this->assertTrue($this->service->placeMatchesTargetLocation([
            'formattedAddress' => 'Oak Park, IL',
            'location' => ['latitude' => 41.88, 'longitude' => -87.78],
        ], 'Chicago, IL', $bounds));
    }
}
