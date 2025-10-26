<?php

namespace App\Console\Commands;

use App\Models\RentalRequestResponse;
use App\Helpers\ProposalDeliveryHelper;
use Illuminate\Console\Command;

class RecalculateProposalDelivery extends Command
{
    protected $signature = 'proposals:recalculate-delivery';
    protected $description = 'Пересчет стоимости доставки для существующих предложений';

    public function handle()
    {
        $this->info('Начинаем пересчет доставки для предложений...');

        $proposals = RentalRequestResponse::whereNotNull('price_breakdown')
            ->where('status', 'pending')
            ->get();

        $this->info("Найдено предложений: " . $proposals->count());

        $updatedCount = 0;

        foreach ($proposals as $proposal) {
            try {
                $rentalRequest = $proposal->rentalRequest;
                $lessorCompany = $proposal->lessor->company;

                if (!$rentalRequest || !$lessorCompany) {
                    continue;
                }

                // Пересчитываем доставку
                $deliveryCalculation = ProposalDeliveryHelper::calculateDelivery(
                    $rentalRequest,
                    $lessorCompany,
                    [['equipment_id' => $proposal->equipment_id, 'quantity' => $proposal->proposed_quantity]]
                );

                // Обновляем price_breakdown
                $priceBreakdown = $proposal->price_breakdown;
                if (is_string($priceBreakdown)) {
                    $priceBreakdown = json_decode($priceBreakdown, true);
                }

                $priceBreakdown['delivery_breakdown'] = $deliveryCalculation;

                // Пересчитываем общую стоимость
                $basePrice = $priceBreakdown['item_total_customer'] ?? $proposal->proposed_price;
                $newTotalPrice = $basePrice + ($deliveryCalculation['delivery_cost'] ?? 0);

                $proposal->update([
                    'price_breakdown' => $priceBreakdown,
                    'proposed_price' => $newTotalPrice
                ]);

                $updatedCount++;
                $this->info("Обновлено предложение ID: " . $proposal->id);

            } catch (\Exception $e) {
                $this->error("Ошибка для предложения {$proposal->id}: " . $e->getMessage());
            }
        }

        $this->info("Пересчет завершен! Обновлено: {$updatedCount} предложений");
    }
}
