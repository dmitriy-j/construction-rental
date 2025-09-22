<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class YandexMapsService
{
    public function calculateDistance(\App\Models\Location $from, \App\Models\Location $to): float
    {
        $apiKey = config('services.yandex_maps.api_key');
        $response = Http::get('https://api.routing.yandex.net/v2/distancematrix', [
            'origins' => "{$from->latitude},{$from->longitude}",
            'destinations' => "{$to->latitude},{$to->longitude}",
            'apikey' => $apiKey,
        ]);

        return $response->json('rows.0.elements.0.distance.value') / 1000;
    }

    private function fallbackDistance(\App\Models\Location $from, \App\Models\Location $to): float
    {
        return (new DeliveryCalculatorService)->haversineDistance(
            $from->latitude,
            $from->longitude,
            $to->latitude,
            $to->longitude
        );
    }
}
