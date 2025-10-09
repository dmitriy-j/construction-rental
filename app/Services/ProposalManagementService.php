<?php

namespace App\Services;

use App\Models\RentalRequest;
use App\Models\RentalRequestResponse;
use App\Models\User;
use App\Models\Equipment;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProposalManagementService
{
    public function createProposal(RentalRequest $request, User $lessor, array $data): RentalRequestResponse
    {
        return DB::transaction(function () use ($request, $lessor, $data) {
            // Только создаем предложение, НЕ создаем заказ
            $proposal = RentalRequestResponse::create([
                'rental_request_id' => $request->id,
                'lessor_id' => $lessor->id,
                'equipment_id' => $data['equipment_id'],
                'proposed_price' => $data['proposed_price'],
                'message' => $data['message'],
                'status' => 'pending',
                'expires_at' => now()->addDays(14)
            ]);

            // Обновление счетчика откликов в заявке
            $request->increment('responses_count');


            // Уведомление арендатора
            try {
                if (class_exists('App\Events\NewProposalReceived')) {
                    event(new \App\Events\NewProposalReceived($proposal));
                }
            } catch (\Exception $e) {
                Log::warning('Event NewProposalReceived not found: ' . $e->getMessage());
            }

            return $proposal;
        });
    }

    // Добавляем отдельный метод для принятия предложения и создания заказа
    public function acceptProposalAndCreateOrder(RentalRequestResponse $proposal): Order
    {
        return DB::transaction(function () use ($proposal) {
            // Проверяем, что предложение еще не было принято
            if ($proposal->status === 'accepted') {
                throw new \Exception('Предложение уже было принято');
            }

            // Отклоняем все другие предложения по этой заявке
            RentalRequestResponse::where('rental_request_id', $proposal->rental_request_id)
                ->where('id', '!=', $proposal->id)
                ->update(['status' => 'rejected']);

            // Принимаем выбранное предложение
            $proposal->update(['status' => 'accepted']);

            // Обновляем статус заявки
            $proposal->rentalRequest->update(['status' => 'processing']);

            // Только теперь создаем заказ
            return $this->createOrderFromProposal($proposal);
        });
    }

    public function acceptProposal(RentalRequestResponse $proposal): void
    {
        DB::transaction(function () use ($proposal) {
            // Проверяем, что предложение еще не было принято
            if ($proposal->status === 'accepted') {
                throw new \Exception('Предложение уже было принято');
            }

            // Отклоняем все другие предложения по этой заявке
            RentalRequestResponse::where('rental_request_id', $proposal->rental_request_id)
                ->where('id', '!=', $proposal->id)
                ->update(['status' => 'rejected']);

            // Принимаем выбранное предложение
            $proposal->update(['status' => 'accepted']);

            // Обновляем статус заявки
            $proposal->rentalRequest->update(['status' => 'processing']);

            // Создаем заказ из предложения
            $this->createOrderFromProposal($proposal);
        });
    }

    private function createOrderFromProposal(RentalRequestResponse $proposal): Order
    {
        try {
            $user = $proposal->rentalRequest->user;
            $equipment = $proposal->equipment;

            // Создаем заказ с существующими полями
            $order = Order::create([
                'user_id' => $user->id,
                'lessee_company_id' => $user->company_id,
                'lessor_company_id' => $equipment->company_id,
                'status' => 'pending_approval',
                'total_amount' => $proposal->proposed_price,
                'start_date' => $proposal->rentalRequest->rental_period_start, // Используем start_date
                'end_date' => $proposal->rentalRequest->rental_period_end,     // Используем end_date
                'delivery_type' => $proposal->rentalRequest->delivery_required ? 'delivery' : 'pickup',
            ]);

            // Создаем элемент заказа (если есть таблица order_items)
            if (class_exists('App\Models\OrderItem')) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'equipment_id' => $equipment->id,
                    'quantity' => 1,
                    'unit_price' => $proposal->proposed_price,
                    'total_price' => $proposal->proposed_price,
                ]);
            }

            // Связываем предложение с заказом
            $proposal->update(['order_id' => $order->id]);

            return $order;

        } catch (\Exception $e) {
            \Log::error('Failed to create order from proposal: ' . $e->getMessage());
            throw new \Exception('Ошибка создания заказа: ' . $e->getMessage());
        }
    }

    public function rejectProposal(RentalRequestResponse $proposal): void
    {
        $proposal->update(['status' => 'rejected']);

        try {
            if (class_exists('App\Events\ProposalRejected')) {
                event(new \App\Events\ProposalRejected($proposal));
            }
        } catch (\Exception $e) {
            Log::warning('Event ProposalRejected not found: ' . $e->getMessage());
        }
    }

    public function counterOffer(RentalRequestResponse $proposal, float $counterPrice, string $message = null): void
    {
        DB::transaction(function () use ($proposal, $counterPrice, $message) {
            $proposal->update([
                'status' => 'counter_offer',
                'counter_price' => $counterPrice,
                'message' => $message ?? $proposal->message
            ]);

            try {
                if (class_exists('App\Events\CounterOfferMade')) {
                    event(new \App\Events\CounterOfferMade($proposal));
                }
            } catch (\Exception $e) {
                Log::warning('Event CounterOfferMade not found: ' . $e->getMessage());
            }
        });
    }

    public static function getNewProposalsCount(User $lessee): int
    {
        try {
            // Проверяем существование необходимых моделей
            if (!class_exists('App\Models\RentalRequestResponse') ||
                !class_exists('App\Models\RentalRequest')) {
                return 0;
            }

            return RentalRequestResponse::whereHas('rentalRequest', function($query) use ($lessee) {
                    $query->where('user_id', $lessee->id);
                })
                ->where('status', 'pending')
                ->where('created_at', '>', now()->subDays(3))
                ->count();

        } catch (\Exception $e) {
            Log::error('Error counting new proposals: ' . $e->getMessage());
            return 0;
        }
    }

    public function getProposalStatistics(User $user): array
    {
        try {
            if (!class_exists('App\Models\RentalRequestResponse')) {
                return [
                    'total' => 0,
                    'pending' => 0,
                    'accepted' => 0,
                    'rejected' => 0
                ];
            }

            $query = RentalRequestResponse::whereHas('rentalRequest', function($query) use ($user) {
                $query->where('user_id', $user->id);
            });

            return [
                'total' => $query->count(),
                'pending' => $query->where('status', 'pending')->count(),
                'accepted' => $query->where('status', 'accepted')->count(),
                'rejected' => $query->where('status', 'rejected')->count(),
                'counter_offers' => $query->where('status', 'counter_offer')->count(),
            ];

        } catch (\Exception $e) {
            Log::error('Error getting proposal statistics: ' . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'accepted' => 0,
                'rejected' => 0,
                'counter_offers' => 0
            ];
        }
    }

    public function validateProposalData(array $data): array
    {
        $errors = [];

        if (!isset($data['proposed_price']) || $data['proposed_price'] <= 0) {
            $errors[] = 'Цена предложения должна быть положительной';
        }

        if (!isset($data['message']) || strlen(trim($data['message'])) < 10) {
            $errors[] = 'Сообщение должно содержать не менее 10 символов';
        }

        if (isset($data['availability_dates']) && !is_array($data['availability_dates'])) {
            $errors[] = 'Даты доступности должны быть в формате массива';
        }

        return $errors;
    }

    public function createPublicProposal(
        RentalRequest $request,
        User $lessor,
        array $pricingData,
        string $message
    ): RentalRequestResponse {
        return DB::transaction(function () use ($request, $lessor, $pricingData, $message) {
            $proposal = RentalRequestResponse::create([
                'rental_request_id' => $request->id,
                'lessor_id' => $lessor->id,
                'equipment_id' => $pricingData['equipment_id'] ?? null,
                'proposed_price' => $pricingData['lessor_price_per_unit'] ?? 0, // Цена арендодателя
                'proposed_quantity' => $pricingData['quantity'] ?? 1,
                'message' => $message,
                'price_breakdown' => $pricingData,
                'platform_markup_details' => $pricingData['platform_markup'] ?? [],
                'final_customer_price' => $pricingData['final_price_per_unit'] ?? 0, // Цена для арендатора
                'status' => 'pending',
                'expires_at' => now()->addDays(14)
            ]);

            // Обновление счетчика откликов
            $request->increment('responses_count');

            // Уведомление
            try {
                if (class_exists('App\Events\NewProposalReceived')) {
                    event(new \App\Events\NewProposalReceived($proposal));
                }
            } catch (\Exception $e) {
                Log::warning('Event NewProposalReceived not found: ' . $e->getMessage());
            }

            return $proposal;
        });
    }

     public function validateProposal(RentalRequest $request, User $lessor, array $equipmentIds): array
    {
        $errors = [];
        $availableEquipment = collect();

        foreach ($equipmentIds as $equipmentId) {
            $equipment = Equipment::find($equipmentId);

            // Проверка принадлежности
            if (!$equipment || $equipment->company_id !== $lessor->company_id) {
                $errors[] = "Техника ID {$equipmentId} не принадлежит вам";
                continue;
            }

            // Проверка соответствия категории
            if (!$this->matchesRequestCategory($equipment, $request)) {
                $errors[] = "Техника '{$equipment->title}' не соответствует категориям заявки";
                continue;
            }

            // Проверка доступности
            $availability = $this->checkEquipmentAvailability($equipment, $request);
            if (!$availability['available']) {
                $errors[] = "Техника '{$equipment->title}' недоступна: {$availability['reason']}";
                continue;
            }

            $availableEquipment->push($equipment);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'available_equipment' => $availableEquipment
        ];
    }

    private function matchesRequestCategory(Equipment $equipment, RentalRequest $request): bool
    {
        $requestCategoryIds = $request->items->pluck('category_id')->unique();
        return $requestCategoryIds->contains($equipment->category_id);
    }

    private function checkEquipmentAvailability(Equipment $equipment, RentalRequest $request): array
    {
        try {
            $availabilityService = app(EquipmentAvailabilityService::class);
            $isAvailable = $availabilityService->isAvailableForPeriod(
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

    public function validateProposalCreation(RentalRequest $rentalRequest, User $lessor, array $proposalData): array
    {
        $errors = [];

        // 1. Check equipment belongs to lessor
        $equipment = Equipment::find($proposalData['equipment_id']);
        if (!$equipment || $equipment->company_id !== $lessor->company_id) {
            $errors[] = 'The selected equipment does not belong to your company.';
        }

        // 2. Check category match
        $requestCategoryIds = $rentalRequest->items->pluck('category_id')->toArray();
        if (!in_array($equipment->category_id, $requestCategoryIds)) {
            $errors[] = 'The equipment category does not match the request.';
        }

        // 3. Check availability
        // ... (Use your existing availability check logic)

        // 4. Validate proposed quantity
        if ($proposalData['quantity'] > $rentalRequest->getRequiredQuantityForCategory($equipment->category_id)) {
            $errors[] = 'The proposed quantity exceeds the requested amount.';
        }

        return $errors;
    }
}
