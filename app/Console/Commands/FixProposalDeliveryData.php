<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CartItem;
use App\Models\RentalRequestResponse;

class FixProposalDeliveryData extends Command
{
    protected $signature = 'fix:proposal-delivery';
    protected $description = 'Fix delivery data in proposal cart items';

    public function handle()
    {
        $this->info('Starting proposal delivery data fix...');

        $cartItems = CartItem::where('is_proposal_item', true)->get();

        foreach ($cartItems as $cartItem) {
            try {
                $proposal = $cartItem->proposal;
                if (!$proposal) continue;

                $priceBreakdown = $proposal->price_breakdown;
                if (is_string($priceBreakdown)) {
                    $priceBreakdown = json_decode($priceBreakdown, true);
                }

                // Извлекаем данные о доставке
                $deliveryBreakdown = $priceBreakdown['delivery_breakdown'] ?? [];
                $hasDelivery = $deliveryBreakdown['delivery_required'] ?? false;
                $deliveryCost = $deliveryBreakdown['delivery_cost'] ?? 0;

                // Для bulk-предложений
                if (isset($priceBreakdown['items']) && is_array($priceBreakdown['items'])) {
                    foreach ($priceBreakdown['items'] as $item) {
                        if ($item['equipment_id'] == $proposal->equipment_id) {
                            $deliveryBreakdown = $item['delivery_breakdown'] ?? [];
                            $hasDelivery = $deliveryBreakdown['delivery_required'] ?? false;
                            $deliveryCost = $deliveryBreakdown['delivery_cost'] ?? 0;
                            break;
                        }
                    }
                }

                // Обновляем cart_item
                $cartItem->update([
                    'delivery_cost' => $deliveryCost,
                    'proposal_data' => array_merge($cartItem->proposal_data ?? [], [
                        'delivery_breakdown' => $deliveryBreakdown,
                        'has_delivery' => $hasDelivery,
                        'delivery_cost' => $deliveryCost,
                        'delivery_fixed_at' => now()->toDateTimeString()
                    ])
                ]);

                $this->info("Fixed cart item {$cartItem->id}");

            } catch (\Exception $e) {
                $this->error("Error fixing cart item {$cartItem->id}: " . $e->getMessage());
            }
        }

        $this->info('Proposal delivery data fixed successfully!');
    }
}
