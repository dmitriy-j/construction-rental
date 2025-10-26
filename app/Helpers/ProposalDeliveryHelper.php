<?php

namespace App\Helpers;

use App\Models\RentalRequest;
use App\Models\Company;
use App\Models\Location;
use App\Models\Equipment;
use App\Services\DeliveryCalculatorService;
use App\Services\TransportCalculatorService;
use Illuminate\Support\Facades\Log;

class ProposalDeliveryHelper
{
    public static function validateDeliveryFeasibility(RentalRequest $request, Company $lessorCompany): array
    {
        Log::info("🔍 Validating delivery feasibility", [
            'request_id' => $request->id,
            'lessor_company_id' => $lessorCompany->id,
            'customer_location_id' => $request->location_id
        ]);

        // Получаем локацию арендатора (строительный объект)
        $customerLocation = Location::find($request->location_id);

        if (!$customerLocation) {
            Log::warning("❌ Customer location not found", ['location_id' => $request->location_id]);
            return [
                'feasible' => false,
                'error' => 'Локация строительного объекта не найдена'
            ];
        }

        // Проверяем, что у компании есть локации
        $hasLocations = Location::where('company_id', $lessorCompany->id)->exists();
        if (!$hasLocations) {
            Log::warning("❌ Lessor has no locations", ['company_id' => $lessorCompany->id]);
            return [
                'feasible' => false,
                'error' => 'У арендодателя не настроены локации техники'
            ];
        }

        Log::info("✅ Delivery validation passed", [
            'customer_location' => $customerLocation->name
        ]);

        return [
            'feasible' => true,
            'error' => null,
            'to_location' => $customerLocation
        ];
    }

    public static function calculateDelivery(RentalRequest $request, Company $lessorCompany, array $equipmentItems): array
    {
        Log::info("🚚 Starting delivery calculation", [
            'rental_request_id' => $request->id,
            'lessor_company_id' => $lessorCompany->id,
            'equipment_count' => count($equipmentItems),
            'total_units' => array_sum(array_column($equipmentItems, 'quantity')),
            'delivery_required' => $request->delivery_required
        ]);

        if (!$request->delivery_required) {
            Log::info("ℹ️ Delivery not required for this request");
            return [
                'delivery_required' => false,
                'delivery_cost' => 0,
                'distance_km' => 0,
                'vehicle_type' => null,
                'rate_per_km' => 0,
                'from_location' => null,
                'to_location' => null,
                'delivery_breakdown' => [],
                'error' => null
            ];
        }

        try {
            // Получаем локацию арендатора (строительный объект)
            $customerLocation = Location::find($request->location_id);
            if (!$customerLocation) {
                throw new \Exception('Локация строительного объекта не найдена');
            }

            // 🔥 ВАЖНО: Определяем основную локацию для доставки
            $deliveryLocation = self::determineDeliveryLocation($equipmentItems, $lessorCompany);

            if (!$deliveryLocation) {
                throw new \Exception('Не удалось определить локацию для доставки оборудования');
            }

            Log::info("📍 Delivery locations identified", [
                'from' => $deliveryLocation->name . ' (' . $deliveryLocation->address . ')',
                'to' => $customerLocation->name . ' (' . $customerLocation->address . ')',
                'equipment_count' => count($equipmentItems)
            ]);

            // Инициализируем сервисы
            $deliveryCalculator = app(DeliveryCalculatorService::class);
            $transportCalculator = app(TransportCalculatorService::class);

            // Рассчитываем расстояние
            $distanceKm = $deliveryCalculator->calculateDistance($deliveryLocation, $customerLocation);

            Log::info("📏 Distance calculated", ['distance_km' => $distanceKm]);

            if ($distanceKm <= 0) {
                throw new \Exception('Не удалось рассчитать расстояние между локациями');
            }

            // 🔥 ОСНОВНОЕ ИЗМЕНЕНИЕ: рассчитываем доставку для КАЖДОЙ единицы техники отдельно
            $totalDeliveryCost = 0;
            $vehicleTypes = [];
            $deliveryBreakdown = [];

            foreach ($equipmentItems as $equipmentItem) {
                $equipment = Equipment::find($equipmentItem['equipment_id']);
                if (!$equipment) {
                    Log::warning("❌ Equipment not found", ['equipment_id' => $equipmentItem['equipment_id']]);
                    continue;
                }

                // Определяем тип транспорта для этого оборудования
                $vehicleType = $transportCalculator->calculateRequiredTransport($equipment);
                $ratePerKm = $transportCalculator->getTransportRate($vehicleType);

                // 🔥 РАСЧЕТ ДЛЯ КАЖДОЙ ЕДИНИЦЫ: расстояние × ставка × количество
                $deliveryCostPerUnit = $distanceKm * $ratePerKm;
                $deliveryCostForEquipment = $deliveryCostPerUnit * $equipmentItem['quantity'];

                $totalDeliveryCost += $deliveryCostForEquipment;
                $vehicleTypes[] = $vehicleType;

                $deliveryBreakdown[] = [
                    'equipment_id' => $equipment->id,
                    'equipment_title' => $equipment->title,
                    'quantity' => $equipmentItem['quantity'],
                    'vehicle_type' => $vehicleType,
                    'rate_per_km' => $ratePerKm,
                    'delivery_cost_per_unit' => $deliveryCostPerUnit,
                    'delivery_cost_total' => $deliveryCostForEquipment,
                    'calculation' => "{$distanceKm} км × {$ratePerKm} ₽/км × {$equipmentItem['quantity']} ед."
                ];

                Log::info("📦 Equipment delivery calculated", [
                    'equipment_id' => $equipment->id,
                    'equipment_title' => $equipment->title,
                    'quantity' => $equipmentItem['quantity'],
                    'vehicle_type' => $vehicleType,
                    'delivery_cost_per_unit' => $deliveryCostPerUnit,
                    'delivery_cost_total' => $deliveryCostForEquipment
                ]);
            }

            // Определяем основной тип транспорта (самый частый)
            $vehicleTypeCounts = array_count_values($vehicleTypes);
            arsort($vehicleTypeCounts);
            $primaryVehicleType = key($vehicleTypeCounts) ?? 'truck_25t';
            $primaryRatePerKm = $transportCalculator->getTransportRate($primaryVehicleType);

            Log::info("✅ Delivery calculation completed", [
                'total_delivery_cost' => $totalDeliveryCost,
                'distance_km' => $distanceKm,
                'vehicle_types' => array_count_values($vehicleTypes),
                'delivery_breakdown_count' => count($deliveryBreakdown)
            ]);

            return [
                'delivery_required' => true,
                'delivery_cost' => $totalDeliveryCost,
                'distance_km' => round($distanceKm, 2),
                'vehicle_type' => $primaryVehicleType,
                'rate_per_km' => $primaryRatePerKm,
                'from_location' => [
                    'id' => $deliveryLocation->id,
                    'name' => $deliveryLocation->name,
                    'address' => $deliveryLocation->address,
                    'formatted_name' => self::formatLocationName($deliveryLocation)
                ],
                'to_location' => [
                    'id' => $customerLocation->id,
                    'name' => $customerLocation->name,
                    'address' => $customerLocation->address,
                    'formatted_name' => self::formatLocationName($customerLocation)
                ],
                'delivery_breakdown' => $deliveryBreakdown,
                'total_units' => array_sum(array_column($equipmentItems, 'quantity')),
                'calculation_notes' => 'Стоимость доставки рассчитана для каждой единицы техники отдельно',
                'error' => null
            ];

        } catch (\Exception $e) {
            Log::error("❌ Delivery calculation failed: " . $e->getMessage(), [
                'request_id' => $request->id,
                'lessor_company_id' => $lessorCompany->id
            ]);

            return [
                'delivery_required' => false,
                'delivery_cost' => 0,
                'distance_km' => 0,
                'vehicle_type' => null,
                'rate_per_km' => 0,
                'from_location' => null,
                'to_location' => null,
                'delivery_breakdown' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Определяем локацию для доставки на основе оборудования
     */
    private static function determineDeliveryLocation(array $equipmentItems, Company $lessorCompany): ?Location
    {
        // Сначала пытаемся получить локацию из первого оборудования
        foreach ($equipmentItems as $item) {
            $equipment = Equipment::with('location')->find($item['equipment_id']);

            if ($equipment && $equipment->location) {
                Log::info("📍 Using equipment location", [
                    'equipment_id' => $equipment->id,
                    'equipment_title' => $equipment->title,
                    'location_id' => $equipment->location->id,
                    'location_name' => $equipment->location->name
                ]);
                return $equipment->location;
            }
        }

        // Если не нашли через оборудование, берем первую локацию компании
        $fallbackLocation = Location::where('company_id', $lessorCompany->id)->first();

        if ($fallbackLocation) {
            Log::warning("⚠️ Using fallback company location", [
                'location_id' => $fallbackLocation->id,
                'location_name' => $fallbackLocation->name
            ]);
        }

        return $fallbackLocation;
    }

    /**
     * Форматируем название локации для отображения
     */
    private static function formatLocationName(Location $location): string
    {
        // Если название неинформативное, используем адрес
        $uninformativeNames = ['техническая база', 'база', 'склад', 'площадка'];

        if (in_array(mb_strtolower($location->name), $uninformativeNames) && !empty($location->address)) {
            return $location->address;
        }

        return $location->name;
    }

    /**
     * 🔥 НОВЫЙ МЕТОД: Получить стоимость доставки для конкретного оборудования
     */
    public static function getDeliveryCostForEquipment(array $deliveryCalculation, int $equipmentId): float
    {
        if (empty($deliveryCalculation['delivery_breakdown'])) {
            return 0;
        }

        foreach ($deliveryCalculation['delivery_breakdown'] as $breakdown) {
            if ($breakdown['equipment_id'] == $equipmentId) {
                return $breakdown['delivery_cost_total'];
            }
        }

        return 0;
    }
}
