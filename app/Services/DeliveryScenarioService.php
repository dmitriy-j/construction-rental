<?php

namespace App\Services;

use App\Models\DeliveryNote;
use App\Models\OrderItem;
use App\Models\Platform; // Исправленный импорт

class DeliveryScenarioService
{
    protected $transportCalculator;

    public function __construct(TransportCalculatorService $transportCalculator)
    {
        $this->transportCalculator = $transportCalculator;
    }

    /**
     * Обработка подтверждения заказа: создание накладных по сценарию
     */
    public function handleOrderConfirmation(OrderItem $item, string $scenario)
    {
        // Только для сценария "Арендодатель организует доставку"
        if ($scenario !== 'lessor') {
            return null;
        }

        return DeliveryNote::create([
            'document_number' => null,
            'issue_date' => now(),
            'type' => DeliveryNote::TYPE_LESSOR_TO_PLATFORM,
            'order_id' => $item->order_id,
            'order_item_id' => $item->id,
            'sender_company_id' => $item->order->lessor_company_id,
            'receiver_company_id' => Platform::getMain()->id,
            'delivery_from_id' => $item->delivery_from_id,
            'delivery_to_id' => $item->delivery_to_id,
            'cargo_description' => $item->equipment->title,
            'cargo_weight' => $item->equipment->getNumericSpecValue('Вес'),
            'cargo_value' => $item->total_price,
            'transport_type' => $this->transportCalculator->calculateRequiredTransport($item->equipment),
            'status' => DeliveryNote::STATUS_DRAFT,
            'distance_km' => $item->distance_km ?? 0,
            'calculated_cost' => $item->delivery_cost,
            'is_mirror' => false,
            'visible_to_lessee' => false,
        ]);
    }

    protected function assignCarrier(DeliveryNote $note)
    {
        $carrier = Company::where('is_carrier', true)
            ->where('status', 'verified')
            ->inRandomOrder()
            ->first();

        if ($carrier) {
            $contactInfo = $carrier->getContactInfo();

            $note->update([
                'carrier_company_id' => $carrier->id,
                'carrier_contact_name' => $contactInfo['name'],
                'carrier_contact_phone' => $contactInfo['phone'],
                'status' => DeliveryNote::STATUS_IN_TRANSIT,
            ]);

            // Отправка уведомления перевозчику
            $carrier->notify(new NewDeliveryJob($note->order));
        }
    }

    private function createDeliveryNote(OrderItem $item, string $scenario): DeliveryNote
    {
        $transportType = $this->transportCalculator->calculateRequiredTransport($item->equipment);

        return DeliveryNote::create([
            'delivery_scenario' => $scenario,
            'type' => $scenario === DeliveryNote::SCENARIO_LESSOR_PLATFORM
                ? DeliveryNote::TYPE_LESSOR_TO_PLATFORM
                : DeliveryNote::TYPE_DIRECT,
            'order_id' => $item->order_id,
            'order_item_id' => $item->id,
            'sender_company_id' => $scenario === DeliveryNote::SCENARIO_LESSOR_PLATFORM
                ? $item->order->lessor_company_id
                : Platform::getMain()->id, // Исправлено
            'receiver_company_id' => $scenario === DeliveryNote::SCENARIO_LESSOR_PLATFORM
                ? Platform::getMain()->id // Исправлено
                : $item->order->lessee_company_id,
            'delivery_from_id' => $item->delivery_from_id,
            'delivery_to_id' => $item->delivery_to_id,
            'cargo_description' => $item->equipment->title,
            'cargo_weight' => $item->equipment->getNumericSpecValue('Вес'),
            'cargo_value' => $item->total_price,
            'transport_type' => $transportType,
            'status' => DeliveryNote::STATUS_DRAFT,
            'is_mirror' => false,
        ]);
    }
}
