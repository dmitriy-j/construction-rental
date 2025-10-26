<?php
// app/Services/BulkProposalService.php - ИСПРАВЛЕННЫЙ

namespace App\Services;

use App\Models\RentalRequest;
use App\Models\RentalRequestResponse;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkProposalService
{
     public function __construct(
        private PricingService $pricingService,
        private EquipmentAvailabilityService $availabilityService
    ) {}


    /**
     * Создание bulk-предложения с несколькими единицами техники
     */
    public function createBulkProposal(RentalRequest $request, User $lessor, array $data): RentalRequestResponse
    {
        return DB::transaction(function () use ($request, $lessor, $data) {
            // Валидация
            $validation = $this->validateBulkProposal($request, $lessor, $data['equipment_items']);
            if (!$validation['valid']) {
                throw new \Exception(implode(', ', $validation['errors']));
            }

            // 🔥 РАСЧЕТ ДОСТАВКИ ДЛЯ BULK-ПРЕДЛОЖЕНИЯ
            $deliveryCalculation = \App\Helpers\ProposalDeliveryHelper::calculateDelivery(
                $request,
                $lessor->company,
                $data['equipment_items']
            );

            // ✅ СОЗДАЕМ КОНТЕЙНЕР БЕЗ equipment_id
            $bulkContainer = RentalRequestResponse::create([
                'rental_request_id' => $request->id,
                'lessor_id' => $lessor->id,
                'equipment_id' => null,
                'proposed_price' => 0,
                'proposed_quantity' => 0,
                'message' => $data['message'],
                'price_breakdown' => [
                    'delivery_breakdown' => $deliveryCalculation,
                    'is_bulk' => true
                ],
                'status' => 'pending',
                'is_bulk_main' => true,
                'is_bulk_item' => false,
                'bulk_parent_id' => null,
                'expires_at' => now()->addDays(14)
            ]);

            $totalPrice = 0;
            $totalQuantity = 0;
            $itemsPricing = [];
            $itemsCount = count($data['equipment_items']);

            // ✅ СОЗДАЕМ КОНКРЕТНЫЕ ПРЕДЛОЖЕНИЯ С ОБОРУДОВАНИЕМ
            foreach ($data['equipment_items'] as $item) {
                $equipment = Equipment::find($item['equipment_id']);

                // 🔥 РАСПРЕДЕЛЕНИЕ СТОИМОСТИ ДОСТАВКИ
                $deliveryCostPerItem = 0;
                if ($deliveryCalculation['delivery_required']) {
                    $deliveryCostPerItem = $deliveryCalculation['delivery_cost'] / $itemsCount;
                }

                $itemPricing = $this->calculateItemPricing($equipment, $item, $request);

                $itemProposal = $this->createBulkProposalItem(
                    $bulkContainer,
                    $equipment,
                    $item['proposed_price'],
                    $item['quantity'],
                    array_merge($itemPricing, [
                        'delivery_cost' => $deliveryCostPerItem,
                        'total_delivery_cost' => $deliveryCalculation['delivery_cost']
                    ])
                );

                $totalPrice += $itemProposal->proposed_price + $deliveryCostPerItem;
                $totalQuantity += $itemProposal->proposed_quantity;
                $itemsPricing[$equipment->id] = $itemPricing;
            }

            // ✅ ОБНОВЛЯЕМ КОНТЕЙНЕР С СУММАРНЫМИ ДАННЫМИ
            $bulkContainer->update([
                'proposed_price' => $totalPrice,
                'proposed_quantity' => $totalQuantity,
                'price_breakdown' => array_merge($bulkContainer->price_breakdown ?? [], [
                    'total_lessor_price' => $totalPrice - $deliveryCalculation['delivery_cost'],
                    'items' => $itemsPricing,
                    'working_hours' => $this->calculateWorkingHours($request),
                    'delivery_breakdown' => $deliveryCalculation,
                    'total_delivery_cost' => $deliveryCalculation['delivery_cost']
                ])
            ]);

            $request->increment('responses_count');

            Log::info('Bulk proposal created', [
                'bulk_proposal_id' => $bulkContainer->id,
                'items_count' => count($data['equipment_items']),
                'total_price' => $totalPrice,
                'delivery_cost' => $deliveryCalculation['delivery_cost']
            ]);

            return $bulkContainer;
        });
    }

    /**
     * Создание отдельной позиции в bulk-предложении
     */
    private function createBulkProposalItem(
        RentalRequestResponse $bulkProposal,
        Equipment $equipment,
        float $proposedPrice,
        int $quantity,
        array $additionalData = []
    ): RentalRequestResponse {
        $workingHours = $this->calculateWorkingHours($bulkProposal->rentalRequest);

        // 🔥 ИСПОЛЬЗУЕМ PricingService ДЛЯ РАСЧЕТА ЦЕН
        $priceCalculation = $this->pricingService->calculateProposalPrice(
            $equipment,
            $bulkProposal->rentalRequest,
            $proposedPrice,
            $workingHours
        );

        $deliveryCostPerItem = $additionalData['delivery_cost'] ?? 0;

        $priceBreakdown = [
            'equipment_id' => $equipment->id,
            'equipment_title' => $equipment->title,
            'quantity' => $quantity,
            'working_hours' => $workingHours,
            'lessor_price_per_unit' => $proposedPrice,
            'customer_price_per_unit' => $priceCalculation['price_per_hour'],
            'platform_fee_per_unit' => $priceCalculation['platform_fee'] / max(1, $workingHours),
            'item_total_lessor' => $proposedPrice * $workingHours * $quantity,
            'item_total_customer' => $priceCalculation['calculated_price'],
            'total_platform_fee' => $priceCalculation['platform_fee'],
            'markup_details' => $priceCalculation['markup_details'] ?? ['type' => 'fixed', 'value' => 100],
            'delivery_breakdown' => [
                'delivery_cost' => $deliveryCostPerItem,
                'total_delivery_cost' => $additionalData['total_delivery_cost'] ?? 0
            ],
            'calculation_details' => [
                'rental_days' => $this->calculateRentalDays($bulkProposal->rentalRequest),
                'hours_per_day' => ($bulkProposal->rentalRequest->rental_conditions['hours_per_shift'] ?? 8) *
                                ($bulkProposal->rentalRequest->rental_conditions['shifts_per_day'] ?? 1)
            ]
        ];

        // 🔥 ОБЩАЯ СТОИМОСТЬ С ДОСТАВКОЙ
        $totalPrice = $priceCalculation['calculated_price'] + $deliveryCostPerItem;

        return RentalRequestResponse::create([
            'rental_request_id' => $bulkProposal->rental_request_id,
            'lessor_id' => $bulkProposal->lessor_id,
            'equipment_id' => $equipment->id,
            'proposed_price' => $totalPrice,
            'proposed_quantity' => $quantity,
            'message' => '',
            'price_breakdown' => $priceBreakdown,
            'status' => 'pending',
            'is_bulk_main' => false,
            'is_bulk_item' => true,
            'bulk_parent_id' => $bulkProposal->id,
            'expires_at' => $bulkProposal->expires_at
        ]);
    }

    /**
     * Расчет стоимости для отдельной позиции
     */
    private function calculateItemPricing(Equipment $equipment, array $item, RentalRequest $request): array
    {
        $workingHours = $this->calculateWorkingHours($request);

        // 🔥 ИСПОЛЬЗУЕМ PricingService ДЛЯ РАСЧЕТА
        $priceCalculation = $this->pricingService->calculateProposalPrice(
            $equipment,
            $request,
            $item['proposed_price'], // Цена арендодателя
            $workingHours
        );

        // 🔥 ИСПРАВЛЕНИЕ: Используем правильные ключи
        $markupDetails = $priceCalculation['markup_details'] ?? [
            'type' => 'fixed',
            'value' => 100
        ];

        return [
            'equipment_id' => $equipment->id,
            'equipment_title' => $equipment->title,
            'quantity' => $item['quantity'],
            'lessor_price_per_unit' => $item['proposed_price'],
            'customer_price_per_unit' => $priceCalculation['price_per_hour'] ?? ($item['proposed_price'] + 100),
            'markup_details' => $markupDetails,
            'working_hours' => $workingHours,
            'item_total_lessor' => $item['proposed_price'] * $item['quantity'] * $workingHours,
            'item_total_customer' => $priceCalculation['calculated_price'] ?? ($item['proposed_price'] * $item['quantity'] * $workingHours + 100 * $workingHours)
        ];
    }

    /**
     * Валидация bulk-предложения
     */
    public function validateBulkProposal(RentalRequest $request, User $lessor, array $equipmentItems): array
    {
        $errors = [];
        $validEquipment = [];

        foreach ($equipmentItems as $item) {
            $equipment = Equipment::find($item['equipment_id']);

            if (!$equipment) {
                $errors[] = "Оборудование ID {$item['equipment_id']} не найдено";
                continue;
            }

            // Проверка принадлежности
            if ($equipment->company_id !== $lessor->company_id) {
                $errors[] = "Оборудование '{$equipment->title}' не принадлежит вашей компании";
                continue;
            }

            // Проверка категории
            if (!$this->matchesRequestCategory($equipment, $request)) {
                $errors[] = "Оборудование '{$equipment->title}' не соответствует категориям заявки";
                continue;
            }

            // Проверка доступности
            $availability = $this->checkEquipmentAvailability($equipment, $request);
            if (!$availability['available']) {
                $errors[] = "Оборудование '{$equipment->title}' недоступно: {$availability['reason']}";
                continue;
            }

            $validEquipment[] = $equipment;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'valid_equipment' => $validEquipment
        ];
    }

    /**
     * Расчет рабочих часов
     */
    private function calculateWorkingHours(RentalRequest $request): int
    {
        $start = \Carbon\Carbon::parse($request->rental_period_start);
        $end = \Carbon\Carbon::parse($request->rental_period_end);
        $days = $start->diffInDays($end) + 1;

        $shiftHours = $request->rental_conditions['hours_per_shift'] ?? 8;
        $shiftsPerDay = $request->rental_conditions['shifts_per_day'] ?? 1;

        return $days * $shiftHours * $shiftsPerDay;
    }

    /**
     * Проверка соответствия категории заявки
     */
    private function matchesRequestCategory(Equipment $equipment, RentalRequest $request): bool
    {
        $requestCategoryIds = $request->items->pluck('category_id')->unique();
        return $requestCategoryIds->contains($equipment->category_id);
    }

    /**
     * Проверка доступности оборудования
     */
    private function checkEquipmentAvailability(Equipment $equipment, RentalRequest $request): array
    {
        try {
            $isAvailable = $this->availabilityService->isAvailableForPeriod(
                $equipment->id,
                $request->rental_period_start,
                $request->rental_period_end
            );

            return [
                'available' => $isAvailable,
                'reason' => $isAvailable ? 'Доступна' : 'Занята на указанный период'
            ];

        } catch (\Exception $e) {
            return [
                'available' => false,
                'reason' => 'Ошибка проверки доступности: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Получение bulk-предложения с дочерними элементами
     */
    public function getBulkProposalWithItems(int $proposalId): RentalRequestResponse
    {
        return RentalRequestResponse::with([
            'bulkItems.equipment',
            'bulkItems.equipment.category',
            'bulkItems.equipment.rentalTerms',
            'rentalRequest',
            'rentalRequest.items'
        ])
        ->where('id', $proposalId)
        ->where('is_bulk_main', true)
        ->firstOrFail();
    }

    /**
     * Создание заказа из bulk-предложения
     */

    public function createOrderFromBulkProposal(RentalRequestResponse $bulkProposal): Order
    {
        if (!$bulkProposal->is_bulk_main) {
            throw new \Exception('Только bulk-контейнер может быть преобразован в заказ');
        }

        $bulkItems = $bulkProposal->bulkItems;
        if ($bulkItems->isEmpty()) {
            throw new \Exception('Bulk-предложение не содержит позиций оборудования');
        }

        return DB::transaction(function () use ($bulkProposal, $bulkItems) {
            // Создаем заказ
            $order = $this->createOrderContainer($bulkProposal);

            foreach ($bulkItems as $itemProposal) {
                $this->addOrderItemFromProposal($order, $itemProposal);
            }

            // Связываем предложения с заказом
            $bulkProposal->update(['order_id' => $order->id]);
            $bulkItems->each(function ($item) use ($order) {
                $item->update(['order_id' => $order->id]);
            });

            return $order;
        });
    }

    private function createOrderContainer(RentalRequestResponse $bulkProposal): Order
    {
        $user = $bulkProposal->rentalRequest->user;
        $firstEquipment = $bulkProposal->bulkItems->first()->equipment;

        return Order::create([
            'user_id' => $user->id,
            'lessee_company_id' => $user->company_id,
            'lessor_company_id' => $firstEquipment->company_id,
            'status' => 'pending_approval',
            'total_amount' => $bulkProposal->proposed_price,
            'start_date' => $bulkProposal->rentalRequest->rental_period_start,
            'end_date' => $bulkProposal->rentalRequest->rental_period_end,
            'delivery_type' => $bulkProposal->rentalRequest->delivery_required ? 'delivery' : 'pickup',
        ]);
    }

    private function addOrderItemFromProposal(Order $order, RentalRequestResponse $proposal): void
    {
        $workingHours = $this->calculateWorkingHours($proposal->rentalRequest);

        // 🔥 РАСЧЕТ ЦЕН ДЛЯ ЗАКАЗА (здесь уже будут правильные цены с наценкой)
        $priceCalculation = $this->pricingService->calculateProposalPrice(
            $proposal->equipment,
            $proposal->rentalRequest,
            $proposal->proposed_price,
            $workingHours
        );

        OrderItem::create([
            'order_id' => $order->id,
            'equipment_id' => $proposal->equipment_id,
            'rental_term_id' => $proposal->equipment->rentalTerms->first()->id,
            'quantity' => $proposal->proposed_quantity,
            'base_price' => $priceCalculation['calculated_price'],
            'price_per_unit' => $priceCalculation['price_per_hour'],
            'fixed_customer_price' => $priceCalculation['price_per_hour'], // Цена для арендатора
            'fixed_lessor_price' => $proposal->proposed_price, // Цена арендодателя
            'platform_fee' => $priceCalculation['platform_fee'],
            'total_price' => $priceCalculation['calculated_price'],
            'period_count' => $workingHours,
            'status' => OrderItem::STATUS_PENDING,
        ]);
    }
}
