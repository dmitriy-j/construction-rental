<?php
// app/Services/PublicPricingService.php

namespace App\Services;

use App\Models\RentalRequest;
use App\Models\Equipment;
use App\Models\RentalRequestResponse;

class PublicPricingService
{
    public function calculatePlatformMarkup(RentalRequest $request, Equipment $equipment, float $proposedPrice): array
    {
        // Базовая цена арендодателя
        $lessorPrice = $proposedPrice;

        // Расчет экономии клиента
        $clientSaving = $request->calculateClientSaving($proposedPrice);

        // Комбинированная наценка: 100₽ + 30% от экономии
        $fixedMarkup = 100;
        $percentageMarkup = $clientSaving * 0.3;
        $totalMarkup = $fixedMarkup + $percentageMarkup;

        // Финальная цена для арендатора
        $finalPrice = $lessorPrice + $totalMarkup;

        return [
            'lessor_price' => $lessorPrice,
            'client_saving' => $clientSaving,
            'platform_markup' => [
                'fixed' => $fixedMarkup,
                'percentage' => $percentageMarkup,
                'total' => $totalMarkup,
            ],
            'final_price' => $finalPrice,
            'calculation_formula' => 'lessor_price + 100 + (client_saving × 0.3)'
        ];
    }

    public function createProposalWithMarkup(RentalRequest $request, array $data): RentalRequestResponse
    {
        $equipment = Equipment::findOrFail($data['equipment_id']);

        // Расчет наценки
        $markupDetails = $this->calculatePlatformMarkup(
            $request,
            $equipment,
            $data['proposed_price']
        );

        // Создание предложения
        $proposal = RentalRequestResponse::create([
            'rental_request_id' => $request->id,
            'lessor_id' => auth()->id(),
            'equipment_id' => $data['equipment_id'],
            'proposed_price' => $data['proposed_price'],
            'proposed_quantity' => $data['proposed_quantity'] ?? 1,
            'message' => $data['message'],
            'price_breakdown' => $markupDetails,
            'platform_markup_details' => $markupDetails['platform_markup'],
            'status' => 'pending',
            'reservation_status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        // Резервирование оборудования на 24 часа при подтверждении
        $this->reserveEquipmentIfAccepted($proposal);

        return $proposal;
    }

    private function reserveEquipmentIfAccepted(RentalRequestResponse $proposal): void
    {
        // Эта функция будет вызвана при принятии предложения
        // Пока просто устанавливаем флаг резервирования
        $proposal->update([
            'reservation_status' => 'pending',
            'reserved_until' => now()->addDay()
        ]);
    }
}
