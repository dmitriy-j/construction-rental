<?php

namespace App\Services;

use App\Models\DeliveryNote;
use App\Models\OrderItem;
use App\Platform;
use App\Services\TransportCalculatorService;

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
    public function handleOrderConfirmation(OrderItem $item, string $scenario = 'lessor')
    {
        // Конвертируем текстовый сценарий в константу
        $deliveryScenario = ($scenario === 'lessor')
            ? DeliveryNote::SCENARIO_LESSOR_PLATFORM
            : DeliveryNote::SCENARIO_PLATFORM_DIRECT;

        $note = $this->createDeliveryNote($item, $deliveryScenario);

        // Для сценария 1 создаем зеркальную накладную
        if ($deliveryScenario === DeliveryNote::SCENARIO_LESSOR_PLATFORM) {
            $note->createMirrorNote();
        }

        return $note;
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
                'status' => DeliveryNote::STATUS_IN_TRANSIT
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
                : Platform::main()->id,
            'receiver_company_id' => $scenario === DeliveryNote::SCENARIO_LESSOR_PLATFORM
                ? Platform::main()->id
                : $item->order->lessee_company_id,
            'delivery_from_id' => $item->delivery_from_id,
            'delivery_to_id' => $item->delivery_to_id,
            'cargo_description' => $item->equipment->title,
            'cargo_weight' => $item->equipment->getNumericSpecValue('Вес'),
            'cargo_value' => $item->total_price,
            'transport_type' => $transportType,
            'status' => DeliveryNote::STATUS_DRAFT
        ]);
    }
}
