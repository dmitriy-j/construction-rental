<?php

namespace App\Listeners;

use App\Events\DeliveryNoteSigned;
use App\Models\Equipment;
use App\Services\UPDPdfGenerator;

class UpdateEquipmentLocation
{
    public function handle(DeliveryNoteSigned $event)
    {
        $note = $event->deliveryNote;

        // Обновляем только при доставке арендатору
        if ($note->type === DeliveryNote::TYPE_PLATFORM_TO_LESSEE) {
            Equipment::where('id', $note->orderItem->equipment_id)
                ->update(['location_id' => $note->delivery_to_id]);
        }

        // Генерация PDF
        $pdfGenerator = app(UPDPdfGenerator::class);
        $filePath = $pdfGenerator->saveDeliveryNotePdf($note);
        $note->update(['document_path' => $filePath]);

        // Активация заказа при финальной доставке
        if ($note->type === DeliveryNote::TYPE_PLATFORM_TO_LESSEE) {
            $note->order->update(['status' => Order::STATUS_ACTIVE]);

            // Отправка уведомления
            $note->order->user->notify(new \App\Notifications\EquipmentDelivered($note));
        }
    }
}
