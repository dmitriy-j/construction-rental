<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeliveryCalculatorService
{
    public function calculateDistance(Location $from, Location $to): float
    {
        $cacheKey = "distance_{$from->id}_{$to->id}";

        // Пытаемся получить из кэша
        if (Cache::has($cacheKey)) {
            $distance = Cache::get($cacheKey);
            Log::debug("Using cached distance", ['distance' => $distance]);
            return $this->applyRoadCoefficient($distance);
        }

        // 🔥 ДОБАВИМ ПРОВЕРКУ КООРДИНАТ
        Log::info("📍 Checking coordinates for distance calculation", [
            'from' => [
                'id' => $from->id,
                'name' => $from->name,
                'latitude' => $from->latitude,
                'longitude' => $from->longitude
            ],
            'to' => [
                'id' => $to->id,
                'name' => $to->name,
                'latitude' => $to->latitude,
                'longitude' => $to->longitude
            ]
        ]);

        // Если есть координаты - используем Haversine
        if ($this->hasValidCoordinates($from) && $this->hasValidCoordinates($to)) {
            $distance = $this->haversineDistance(
                $from->latitude,
                $from->longitude,
                $to->latitude,
                $to->longitude
            );

            Log::info("📏 Haversine distance result", [
                'from_lat' => $from->latitude,
                'from_lon' => $from->longitude,
                'to_lat' => $to->latitude,
                'to_lon' => $to->longitude,
                'distance_km_raw' => $distance,
                'distance_km_with_coefficient' => $this->applyRoadCoefficient($distance)
            ]);

            Cache::put($cacheKey, $distance, now()->addDays(30));

            return $this->applyRoadCoefficient($distance);
        }

        // Если нет координат - пытаемся геокодировать
        try {
            Log::warning("⚠️ No coordinates, geocoding addresses", [
                'from_address' => $from->address,
                'to_address' => $to->address
            ]);

            $fromCoords = $this->geocodeLocation($from);
            $toCoords = $this->geocodeLocation($to);

            $distance = $this->haversineDistance(
                $fromCoords['lat'],
                $fromCoords['lon'],
                $toCoords['lat'],
                $toCoords['lon']
            );

            Log::info("🎯 Geocoded distance result", [
                'from_geocoded' => $fromCoords,
                'to_geocoded' => $toCoords,
                'distance_km' => $distance
            ]);

            $this->updateLocationCoordinates($from, $fromCoords);
            $this->updateLocationCoordinates($to, $toCoords);

            Cache::put($cacheKey, $distance, now()->addDays(30));

            return $this->applyRoadCoefficient($distance);

        } catch (\Exception $e) {
            Log::error('❌ Geocoding failed: '.$e->getMessage(), [
                'from_address' => $from->address,
                'to_address' => $to->address
            ]);
            return 0;
        }
    }

    private function hasValidCoordinates(Location $location): bool
    {
        // 🔥 УЛУЧШЕННАЯ ПРОВЕРКА КООРДИНАТ
        $hasCoords = $location->latitude && $location->longitude;

        if ($hasCoords) {
            // Проверяем, что координаты в разумных пределах для России
            $validLat = $location->latitude >= 40 && $location->latitude <= 80;
            $validLon = $location->longitude >= 20 && $location->longitude <= 180;

            if (!$validLat || !$validLon) {
                Log::warning("⚠️ Coordinates out of bounds for Russia", [
                    'location_id' => $location->id,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude
                ]);
                return false;
            }
        }

        return $hasCoords;
    }

    private function geocodeLocation(Location $location): array
    {
        $apiKey = config('services.yandex_maps.api_key');

        Log::info("🗺️ Geocoding address: " . $location->address);

        $response = Http::timeout(10)->get('https://geocode-maps.yandex.ru/1.x/', [
            'geocode' => $location->address,
            'apikey' => $apiKey,
            'format' => 'json',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Geocoding API error: ' . $response->status());
        }

        $data = $response->json();

        if (empty($data['response']['GeoObjectCollection']['featureMember'][0])) {
            Log::error("❌ No geocoding results for address: " . $location->address);
            throw new \Exception('No geocoding results for address: '.$location->address);
        }

        $pos = $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'];
        [$lon, $lat] = explode(' ', $pos);

        Log::info("✅ Geocoding successful", [
            'address' => $location->address,
            'lat' => $lat,
            'lon' => $lon
        ]);

        return ['lat' => (float) $lat, 'lon' => (float) $lon];
    }

    // 🔥 УЛУЧШЕННАЯ ФОРМУЛА HAVERSINE С ПРОВЕРКАМИ
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        // Проверяем валидность координат
        if (abs($lat1) > 90 || abs($lat2) > 90 || abs($lon1) > 180 || abs($lon2) > 180) {
            Log::error("❌ Invalid coordinates in haversine", [
                'lat1' => $lat1, 'lon1' => $lon1,
                'lat2' => $lat2, 'lon2' => $lon2
            ]);
            return 0;
        }

        $earthRadius = 6371; // Радиус Земли в км

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        Log::debug("Haversine calculation", [
            'input' => [$lat1, $lon1, $lat2, $lon2],
            'distance_km' => $distance
        ]);

        return $distance;
    }

    private function applyRoadCoefficient(float $distance): float
    {
        $coefficient = config('services.yandex_maps.coefficient', 1.3);
        $result = $distance * $coefficient;

        Log::debug("Applied road coefficient", [
            'raw_distance' => $distance,
            'coefficient' => $coefficient,
            'result' => $result
        ]);

        return $result;
    }

    private function updateLocationCoordinates(Location $location, array $coords): void
    {
        $location->update([
            'latitude' => $coords['lat'],
            'longitude' => $coords['lon'],
        ]);

        Log::info("✅ Updated location coordinates", [
            'location_id' => $location->id,
            'latitude' => $coords['lat'],
            'longitude' => $coords['lon']
        ]);
    }
}
