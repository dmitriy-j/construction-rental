<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class DeliveryCalculatorService
{
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $response = Http::get("http://router.project-osrm.org/route/v1/driving/$lon1,$lat1;$lon2,$lat2?overview=false");

        if ($response->successful()) {
            $data = $response->json();
            return $data['routes'][0]['distance'] / 1000;
        }

        return $this->haversineDistance($lat1, $lon1, $lat2, $lon2);
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
}
