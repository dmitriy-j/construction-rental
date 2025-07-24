<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Location;

class DeliveryCalculatorService
{
    public function calculateDistance(Location $from, Location $to): float
    {
        $cacheKey = "distance_{$from->id}_{$to->id}";

        // Пытаемся получить из кэша
        if (Cache::has($cacheKey)) {
            $distance = Cache::get($cacheKey);
            return $this->applyRoadCoefficient($distance); // Применяем коэффициент!
        }

        // Если есть координаты - используем Haversine
        if ($this->hasValidCoordinates($from) && $this->hasValidCoordinates($to)) {
            $distance = $this->haversineDistance(
                $from->latitude,
                $from->longitude,
                $to->latitude,
                $to->longitude
            );

            Cache::put($cacheKey, $distance, now()->addDays(30));
            return $this->applyRoadCoefficient($distance); // Применяем коэффициент!
        }

        // Если нет координат - пытаемся геокодировать
        try {
            $fromCoords = $this->geocodeLocation($from);
            $toCoords = $this->geocodeLocation($to);

            $distance = $this->haversineDistance(
                $fromCoords['lat'],
                $fromCoords['lon'],
                $toCoords['lat'],
                $toCoords['lon']
            );

            $this->updateLocationCoordinates($from, $fromCoords);
            $this->updateLocationCoordinates($to, $toCoords);

            Cache::put($cacheKey, $distance, now()->addDays(30));
            return $this->applyRoadCoefficient($distance); // Применяем коэффициент!

        } catch (\Exception $e) {
            Log::error('Geocoding failed: '.$e->getMessage());
            return 0;
        }
    }

    private function hasValidCoordinates(Location $location): bool
    {
        return $location->latitude && $location->longitude;
    }

    private function geocodeLocation(Location $location): array
    {
        $apiKey = config('services.yandex_maps.api_key');
        $response = Http::get('https://geocode-maps.yandex.ru/1.x/', [
            'geocode' => $location->address,
            'apikey' => $apiKey,
            'format' => 'json'
        ]);

        $data = $response->json();

        if (empty($data['response']['GeoObjectCollection']['featureMember'][0])) {
            throw new \Exception('No geocoding results for address: '.$location->address);
        }

        $pos = $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'];
        [$lon, $lat] = explode(' ', $pos);

        return ['lat' => (float)$lat, 'lon' => (float)$lon];
    }

    private function updateLocationCoordinates(Location $location, array $coords)
    {
        $location->update([
            'latitude' => $coords['lat'],
            'longitude' => $coords['lon']
        ]);
    }

    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }

    private function applyRoadCoefficient(float $distance): float
    {
        $coefficient = config('services.yandex_maps.coefficient', 1.3);
        return $distance * $coefficient;
    }
}
