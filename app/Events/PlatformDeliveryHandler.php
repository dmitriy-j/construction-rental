<?php

namespace App\Handlers\Events;

use App\Events\PlatformDeliveryRequested;
use App\Models\Company;
use App\Models\DeliveryNote;

class PlatformDeliveryHandler
{
    public function handle(PlatformDeliveryRequested $event)
    {
        $order = $event->order;

        $carrier = Company::where('is_carrier', true)
            ->where('status', 'verified')
            ->first();

        if (! $carrier) {
            Log::critical('Не найден ни один перевозчик', ['order_id' => $order->id]);

            return;
        }

        // Используем директора как контактное лицо по умолчанию
        $contactName = $carrier->director_name;

        // Извлекаем телефон из поля contacts, если возможно
        $contactPhone = $carrier->phone;
        if (preg_match('/:\s*(\+\d[\d\s\-\(\)]+)/', $carrier->contacts, $matches)) {
            $contactPhone = $matches[1];
        }

        foreach ($order->items as $item) {
            $note = $item->deliveryNote;
            if ($note->delivery_scenario === DeliveryNote::SCENARIO_PLATFORM_DIRECT) {
                $note->update([
                    'carrier_company_id' => $carrier->id,
                    'carrier_contact_name' => $contactName,
                    'carrier_contact_phone' => $contactPhone,
                    'status' => DeliveryNote::STATUS_IN_TRANSIT,
                ]);
            }
        }

        // Отправляем уведомление перевозчику
        $carrier->notify(new \App\Notifications\NewDeliveryJob($order));
    }
}
