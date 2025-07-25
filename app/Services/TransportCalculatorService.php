<?php

namespace App\Services;

use App\Models\Equipment;

class TransportCalculatorService
{
    const VEHICLE_25T = 'truck_25t';
    const VEHICLE_45T = 'truck_45t';
    const VEHICLE_110T = 'truck_110t';

    public function calculateRequiredTransport(Equipment $equipment): string
    {
        // Гарантированная загрузка спецификаций
        if (!$equipment->relationLoaded('specifications')) {
            $equipment->load(['specifications' => function ($query) {
                $query->select('equipment_id', 'key', 'weight', 'length', 'width', 'height');
            }]);
        }

        // Создаем гарантированную коллекцию
        $specs = $equipment->specifications ?? collect();
        $specsMap = $specs->keyBy('key');

        // Безопасное получение значений
        $weight = $specsMap->get('weight')?->weight ?? 0;
        $length = $specsMap->get('length')?->length ?? 0;
        $width = $specsMap->get('width')?->width ?? 0;
        $height = $specsMap->get('height')?->height ?? 0;

        // Проверка по габаритам
        if ($length > 16 || $width > 3.5 || $height > 4) {
            return self::VEHICLE_110T;
        }

        // Проверка по весу
        if ($weight > 45000) return self::VEHICLE_110T;
        if ($weight > 25000) return self::VEHICLE_45T;

        return self::VEHICLE_25T;
    }

    public function getTransportRate(string $vehicleType): float
    {
        $rates = [
            self::VEHICLE_25T => 200,
            self::VEHICLE_45T => 250,
            self::VEHICLE_110T => 350,
        ];

        return $rates[$vehicleType] ?? 200;
    }
}
