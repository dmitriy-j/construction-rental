<?php

namespace App\Services;

use App\Models\Location;
use App\Models\RentalCondition;
use Illuminate\Support\Facades\Http;

class DeliveryCalculatorService
{
    public function calculateDeliveryCost(
        Location $from,
        Location $to,
        RentalCondition $conditions,
        float $weight = 0
    ): float
    {
        // Расчет расстояния через OSRM API
        $distance = $this->calculateDistance(
            $from->latitude,
            $from->longitude,
            $to->latitude,
            $to->longitude
        );

        // Определяем коэффициент веса
        $weightFactor = $this->getWeightFactor($weight);

        // Формула расчета стоимости
        return ($distance * $conditions->delivery_cost_per_km * $weightFactor)
             + $conditions->loading_cost
             + $conditions->unloading_cost;
    }

    private function getWeightFactor(float $weight): float
    {
        if ($weight > 10000) { // Более 10 тонн
            return 1.5;
        } elseif ($weight > 5000) { // 5-10 тонн
            return 1.2;
        }
        return 1.0; // До 5 тонн
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $response = Http::get("http://router.project-osrm.org/route/v1/driving/$lon1,$lat1;$lon2,$lat2?overview=false");

        if ($response->successful()) {
            $data = $response->json();
            return $data['routes'][0]['distance'] / 1000; // Конвертация в км
        }

        // Резервный расчет по формуле Хаверсина
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
