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
    public function createProposal(RentalRequest $request, User $lessor, Equipment $equipment, array $data): RentalRequestResponse
    {
        return DB::transaction(function () use ($request, $lessor, $equipment, $data) {
            // Проверка доступности оборудования
            try {
                $availabilityService = app(EquipmentAvailabilityService::class);
                if (!$availabilityService->isAvailableForPeriod(
                    $equipment->id,
                    $request->rental_period_start,
                    $request->rental_period_end
                )) {
                    throw new \Exception('Оборудование недоступно на указанный период');
                }
            } catch (\Exception $e) {
                Log::error('Equipment availability check failed: ' . $e->getMessage());
                throw new \Exception('Ошибка проверки доступности оборудования: ' . $e->getMessage());
            }

            $proposal = RentalRequestResponse::create([
                'rental_request_id' => $request->id,
                'lessor_id' => $lessor->id,
                'equipment_id' => $equipment->id,
                'proposed_price' => $data['proposed_price'],
                'message' => $data['message'],
                'availability_dates' => $data['availability_dates'] ?? null,
                'additional_terms' => $data['additional_terms'] ?? null,
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

    private function createOrderFromProposal(RentalRequestResponse $proposal): void
    {
        try {
            // Получаем необходимые данные
            $user = $proposal->rentalRequest->user;
            $equipment = $proposal->equipment;

            // Создаем заказ
            $order = Order::create([
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'lessor_company_id' => $equipment->company_id,
                'status' => 'pending_approval',
                'total_amount' => $proposal->proposed_price,
                'request_response_id' => $proposal->id,
                'rental_period_start' => $proposal->rentalRequest->rental_period_start,
                'rental_period_end' => $proposal->rentalRequest->rental_period_end,
                'delivery_required' => $proposal->rentalRequest->delivery_required,
                'location_id' => $proposal->rentalRequest->location_id,
            ]);

            // Создаем элемент заказа
            OrderItem::create([
                'order_id' => $order->id,
                'equipment_id' => $equipment->id,
                'rental_term_id' => $equipment->rentalTerms()->first()?->id,
                'quantity' => 1,
                'unit_price' => $proposal->proposed_price,
                'total_price' => $proposal->proposed_price,
                'rental_period_start' => $proposal->rentalRequest->rental_period_start,
                'rental_period_end' => $proposal->rentalRequest->rental_period_end,
            ]);

            // Связываем предложение с заказом
            $proposal->update(['order_id' => $order->id]);

            Log::info('Order created from proposal', [
                'proposal_id' => $proposal->id,
                'order_id' => $order->id,
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create order from proposal: ' . $e->getMessage());
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
}
