<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeospatialGridService
{
    private const GEOCODING_API_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    /** @var array{status: string, error: string}|null */
    private ?array $lastFailure = null;

    /**
     * Last geocoding failure for the most recent geocode() call, if any.
     *
     * @return array{status: string, error: string}|null
     */
    public function lastFailure(): ?array
    {
        return $this->lastFailure;
    }

    /**
     * Resolve a location string (city, state, zip, or raw coordinates) into a bounding box.
     *
     * @return array{northeast: array{lat: float, lng: float}, southwest: array{lat: float, lng: float}, formatted_address?: string}|null
     */
    public function geocode(string $location, string $apiKey): ?array
    {
        $this->lastFailure = null;
        $location = trim($location);
        if (empty($location) || empty($apiKey)) {
            return null;
        }

        // Check if raw coordinates (e.g. "32.7767, -96.7970")
        if ($this->isCoordinates($location)) {
            [$lat, $lng] = $this->parseCoordinates($location);
            if ($lat !== null && $lng !== null) {
                return array_merge(
                    $this->boundsFromPoint($lat, $lng, 10.0),
                    ['formatted_address' => "{$lat}, {$lng}"]
                );
            }
        }

        try {
            $response = Http::timeout(10)->get(self::GEOCODING_API_URL, [
                'address' => $location,
                'key' => $apiKey,
            ]);

            if ($response->failed()) {
                $body = $response->json();
                $this->lastFailure = [
                    'status' => (string) ($body['status'] ?? $response->status()),
                    'error' => (string) ($body['error_message'] ?? $body['error']['message'] ?? 'Google Geocoding API request failed.'),
                ];
                Log::warning('Google Geocoding API request failed', [
                    'status' => $response->status(),
                    'location' => $location,
                    'error' => $this->lastFailure['error'],
                ]);

                return null;
            }

            $data = $response->json();
            if (($data['status'] ?? '') !== 'OK' || empty($data['results'][0]['geometry'])) {
                $this->lastFailure = [
                    'status' => (string) ($data['status'] ?? 'UNKNOWN'),
                    'error' => (string) ($data['error_message'] ?? 'Google Geocoding returned no results.'),
                ];
                Log::info('Google Geocoding returned no results', [
                    'status' => $this->lastFailure['status'],
                    'location' => $location,
                    'error' => $this->lastFailure['error'],
                ]);

                return null;
            }

            $result = $data['results'][0];
            $geometry = $result['geometry'];

            $bounds = $geometry['bounds'] ?? ($geometry['viewport'] ?? null);

            if ($bounds && isset($bounds['northeast']['lat'], $bounds['southwest']['lat'])) {
                return [
                    'northeast' => [
                        'lat' => (float) $bounds['northeast']['lat'],
                        'lng' => (float) $bounds['northeast']['lng'],
                    ],
                    'southwest' => [
                        'lat' => (float) $bounds['southwest']['lat'],
                        'lng' => (float) $bounds['southwest']['lng'],
                    ],
                    'formatted_address' => $result['formatted_address'] ?? $location,
                ];
            }

            if (isset($geometry['location']['lat'], $geometry['location']['lng'])) {
                return array_merge(
                    $this->boundsFromPoint(
                        (float) $geometry['location']['lat'],
                        (float) $geometry['location']['lng'],
                        10.0
                    ),
                    ['formatted_address' => $result['formatted_address'] ?? $location]
                );
            }
        } catch (Throwable $e) {
            Log::error('Geocoding exception', [
                'location' => $location,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Check if a string represents raw decimal coordinates (e.g. "32.7767, -96.7970").
     */
    public function isCoordinates(string $input): bool
    {
        return (bool) preg_match('/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?),\s*[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/', trim($input));
    }

    /**
     * Parse coordinates from a string.
     *
     * @return array{0: float|null, 1: float|null}
     */
    public function parseCoordinates(string $input): array
    {
        $parts = explode(',', trim($input));
        if (count($parts) === 2) {
            $lat = filter_var(trim($parts[0]), FILTER_VALIDATE_FLOAT);
            $lng = filter_var(trim($parts[1]), FILTER_VALIDATE_FLOAT);

            if ($lat !== false && $lng !== false) {
                return [(float) $lat, (float) $lng];
            }
        }

        return [null, null];
    }

    /**
     * Generate a square bounding box centered around a single coordinate point.
     *
     * @return array{northeast: array{lat: float, lng: float}, southwest: array{lat: float, lng: float}}
     */
    public function boundsFromPoint(float $lat, float $lng, float $radiusKm = 10.0): array
    {
        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / (111.0 * max(0.01, cos(deg2rad($lat))));

        return [
            'northeast' => [
                'lat' => round($lat + $latDelta, 7),
                'lng' => round($lng + $lngDelta, 7),
            ],
            'southwest' => [
                'lat' => round($lat - $latDelta, 7),
                'lng' => round($lng - $lngDelta, 7),
            ],
        ];
    }

    /**
     * Dynamically partition a bounding box into an N x M matrix of smaller rectangular sub-grids.
     *
     * @param  array  $bounds  Array containing northeast/southwest or north/south/east/west coordinates
     * @param  float  $stepKm  Desired grid cell size in kilometers (e.g. 2.5km to 5.0km)
     * @param  int  $targetLimit  Requested lead extraction limit
     * @param  int  $maxCells  Safety ceiling for total grid cells
     * @return array<int, array{index: int, row: int, col: int, low: array{latitude: float, longitude: float}, high: array{latitude: float, longitude: float}, center: array{latitude: float, longitude: float}}>
     */
    public function generateGrid(array $bounds, float $stepKm = 0.0, int $targetLimit = 100, int $maxCells = 64): array
    {
        $north = (float) ($bounds['northeast']['lat'] ?? ($bounds['high']['latitude'] ?? ($bounds['north'] ?? 0)));
        $south = (float) ($bounds['southwest']['lat'] ?? ($bounds['low']['latitude'] ?? ($bounds['south'] ?? 0)));
        $east = (float) ($bounds['northeast']['lng'] ?? ($bounds['high']['longitude'] ?? ($bounds['east'] ?? 0)));
        $west = (float) ($bounds['southwest']['lng'] ?? ($bounds['low']['longitude'] ?? ($bounds['west'] ?? 0)));

        if ($north < $south) {
            [$north, $south] = [$south, $north];
        }
        if ($east < $west) {
            [$east, $west] = [$west, $east];
        }

        // Expand single-point or near-zero bounds
        if (abs($north - $south) < 1e-6) {
            $north += 0.05;
            $south -= 0.05;
        }
        if (abs($east - $west) < 1e-6) {
            $east += 0.05;
            $west -= 0.05;
        }

        $midLat = ($north + $south) / 2.0;
        $centerLat = $midLat;
        $centerLng = ($east + $west) / 2.0;

        $heightKm = abs($north - $south) * 111.0;
        $widthKm = abs($east - $west) * 111.0 * max(0.01, cos(deg2rad($midLat)));

        // Automatically determine optimal stepKm if not explicitly specified
        if ($stepKm <= 0.0) {
            if ($targetLimit <= 60) {
                $stepKm = max(4.0, max($heightKm, $widthKm) / 2.0);
            } elseif ($targetLimit <= 150) {
                $stepKm = 3.5;
            } elseif ($targetLimit <= 300) {
                $stepKm = 2.5;
            } else {
                $stepKm = 2.0;
            }
        }

        $rows = max(1, (int) ceil($heightKm / $stepKm));
        $cols = max(1, (int) ceil($widthKm / $stepKm));

        // If target lead limit is high, ensure a minimum grid density
        if ($targetLimit > 60) {
            $minCellsRequired = (int) ceil($targetLimit / 50.0);
            while (($rows * $cols) < $minCellsRequired && ($rows * $cols) < $maxCells) {
                if ($rows <= $cols) {
                    $rows++;
                } else {
                    $cols++;
                }
            }
        }

        // Clamp to maxCells while preserving aspect ratio
        if (($rows * $cols) > $maxCells) {
            $scale = sqrt($maxCells / ($rows * $cols));
            $rows = max(1, (int) floor($rows * $scale));
            $cols = max(1, (int) floor($cols * $scale));
        }

        $latStep = ($north - $south) / $rows;
        $lngStep = ($east - $west) / $cols;

        $cells = [];
        for ($r = 0; $r < $rows; $r++) {
            $cellSouth = $south + ($r * $latStep);
            $cellNorth = ($r === $rows - 1) ? $north : ($cellSouth + $latStep);

            for ($c = 0; $c < $cols; $c++) {
                $cellWest = $west + ($c * $lngStep);
                $cellEast = ($c === $cols - 1) ? $east : ($cellWest + $lngStep);

                $cellMidLat = ($cellSouth + $cellNorth) / 2.0;
                $cellMidLng = ($cellWest + $cellEast) / 2.0;

                $distFromCenter = $this->distanceKm($cellMidLat, $cellMidLng, $centerLat, $centerLng);

                $cells[] = [
                    'index' => count($cells),
                    'row' => $r,
                    'col' => $c,
                    'low' => [
                        'latitude' => round($cellSouth, 7),
                        'longitude' => round($cellWest, 7),
                    ],
                    'high' => [
                        'latitude' => round($cellNorth, 7),
                        'longitude' => round($cellEast, 7),
                    ],
                    'center' => [
                        'latitude' => round($cellMidLat, 7),
                        'longitude' => round($cellMidLng, 7),
                    ],
                    '_dist' => $distFromCenter,
                ];
            }
        }

        // Sort cells center-outward so dense city centers are extracted first
        usort($cells, fn ($a, $b) => $a['_dist'] <=> $b['_dist']);

        foreach ($cells as $idx => &$cell) {
            $cell['index'] = $idx;
            unset($cell['_dist']);
        }
        unset($cell);

        return $cells;
    }

    /**
     * Calculate great-circle distance between two coordinate points in kilometers using Haversine formula.
     */
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}

