<?php

namespace App\Services;

use App\Models\RentalRequest;
use App\Models\RentalRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RentalRequestService
{
    public function createRentalRequest(array $data, User $user): RentalRequest
    {
        return DB::transaction(function () use ($data, $user) {
            if (empty($data['items']) || !is_array($data['items'])) {
                throw new \Exception('Заявка должна содержать хотя бы одну позицию оборудования');
            }

            $requestData = $this->prepareRequestData($data, $user);
            $rentalRequest = RentalRequest::create($requestData);

            $this->createRequestItems($rentalRequest, $data['items']);
            $rentalRequest->load('items');
            $rentalRequest->calculateBudget();

            \Log::info('Rental request created successfully', [
                'request_id' => $rentalRequest->id,
                'total_budget' => $rentalRequest->total_budget,
                'items_count' => $rentalRequest->items->count()
            ]);

            return $rentalRequest->refresh();
        });
    }

    private function prepareRequestData(array $data, User $user): array
    {
        return [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'title' => $data['title'],
            'description' => $data['description'],
            'hourly_rate' => (float) $data['hourly_rate'],
            'rental_conditions' => $data['rental_conditions'] ?? $this->getDefaultRentalConditions(),
            'rental_period_start' => $data['rental_period_start'],
            'rental_period_end' => $data['rental_period_end'],
            'location_id' => $data['location_id'],
            'delivery_required' => $data['delivery_required'] ?? false,
            'status' => 'active',
            'expires_at' => now()->addDays(30),
            // Временные значения
            'total_budget' => 0,
            'calculated_budget_from' => 0,
            'calculated_budget_to' => 0,
            'total_equipment_quantity' => 0
        ];
    }

    private function createRequestItems(RentalRequest $rentalRequest, array $items): void
    {
        foreach ($items as $item) {
            \Log::debug('🔧 Creating rental request item with metadata', [
                'category_id' => $item['category_id'],
                'specifications_count' => count($item['specifications'] ?? []),
                'metadata_count' => count($item['custom_specs_metadata'] ?? [])
            ]);

            RentalRequestItem::create([
                'rental_request_id' => $rentalRequest->id,
                'category_id' => $item['category_id'],
                'quantity' => $item['quantity'],
                'hourly_rate' => $item['hourly_rate'] ?? null,
                'use_individual_conditions' => $item['use_individual_conditions'] ?? false,
                'individual_conditions' => $item['individual_conditions'] ?? null,
                'specifications' => $item['specifications'] ?? null,
                // ⚠️ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Добавляем сохранение метаданных
                'custom_specs_metadata' => $item['custom_specs_metadata'] ?? null,
                'calculated_price' => 0
            ]);
        }

        $rentalRequest->load('items');
    }

    private function getDefaultRentalConditions(): array
    {
        return [
            'payment_type' => 'hourly',
            'hours_per_shift' => 8,
            'shifts_per_day' => 1,
            'transportation_organized_by' => 'lessor',
            'gsm_payment' => 'included',
            'accommodation_payment' => false,
            'extension_possibility' => true,
            'operator_included' => false
        ];
    }

    /**
     * Метод для обновления бюджета существующей заявки
     */
    public function updateRentalRequestBudget(RentalRequest $rentalRequest): void
    {
        $rentalRequest->load('items');
        $rentalRequest->calculateBudget();
    }

    public function updateRentalRequest(RentalRequest $rentalRequest, array $validatedData)
    {
        try {
            DB::beginTransaction();

            \Log::info('🔧 RentalRequestService: update started', [
                'request_id' => $rentalRequest->id,
                'items_count' => count($validatedData['items'] ?? []),
                'has_metadata' => !empty($validatedData['items'][0]['custom_specs_metadata'] ?? [])
            ]);

            // Обновление основной информации заявки
            $rentalRequest->update([
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'hourly_rate' => $validatedData['hourly_rate'],
                'rental_period_start' => $validatedData['rental_period_start'],
                'rental_period_end' => $validatedData['rental_period_end'],
                'location_id' => $validatedData['location_id'],
                'rental_conditions' => $validatedData['rental_conditions'] ?? [],
                'delivery_required' => $validatedData['delivery_required'] ?? false,
            ]);

            // Удаляем существующие позиции и создаем новые
            $rentalRequest->items()->delete();

            foreach ($validatedData['items'] as $itemData) {
                \Log::debug('🔧 Creating/updating item with metadata', [
                    'category_id' => $itemData['category_id'],
                    'specifications_count' => count($itemData['specifications'] ?? []),
                    'metadata_count' => count($itemData['custom_specs_metadata'] ?? []),
                    'metadata_keys' => array_keys($itemData['custom_specs_metadata'] ?? [])
                ]);

                $rentalRequest->items()->create([
                    'category_id' => $itemData['category_id'],
                    'quantity' => $itemData['quantity'],
                    'hourly_rate' => $itemData['hourly_rate'] ?? null,
                    'specifications' => $itemData['specifications'] ?? [],
                    // ⚠️ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Сохраняем метаданные кастомных спецификаций
                    'custom_specs_metadata' => $itemData['custom_specs_metadata'] ?? [],
                    'use_individual_conditions' => $itemData['use_individual_conditions'] ?? false,
                    'individual_conditions' => $itemData['individual_conditions'] ?? [],
                ]);
            }

            // Пересчитываем и сохраняем общий бюджет
            $rentalRequest->calculateBudget();
            $rentalRequest->save();

            DB::commit();

            // Загружаем обновленные отношения с метаданными
            $updatedRequest = $rentalRequest->fresh(['items.category', 'location']);

            \Log::info('✅ RentalRequestService: update completed', [
                'request_id' => $updatedRequest->id,
                'items_count' => $updatedRequest->items->count(),
                'items_with_metadata' => $updatedRequest->items->filter(fn($item) => !empty($item->custom_specs_metadata))->count(),
                'total_budget' => $updatedRequest->total_budget
            ]);

            return $updatedRequest;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('❌ RentalRequestService update error: ' . $e->getMessage(), [
                'request_id' => $rentalRequest->id,
                'validated_data_keys' => array_keys($validatedData)
            ]);
            throw $e;
        }
    }

    public function getActiveRequestsForLessor(User $user, array $filters = [], int $perPage = 15)
    {
        $query = RentalRequest::active()
            ->public()
            ->with(['items.category', 'location']);

        // Применяем фильтры
        if (!empty($filters['category_id'])) {
            $query->whereHas('items', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (!empty($filters['budget_max'])) {
            $query->where('total_budget', '<=', $filters['budget_max']);
        }

        // Сортировка
        if (!empty($filters['sort_by'])) {
            switch ($filters['sort_by']) {
                case 'budget':
                    $query->orderBy('total_budget', 'desc');
                    break;
                case 'newest':
                default:
                    $query->orderBy('created_at', 'desc');
            }
        }

        // ИСПРАВЛЕНИЕ: Возвращаем пагинатор вместо коллекции
        return $query->paginate($perPage);
    }

    /**
     * Создание заявки через API с поддержкой метаданных
     */
    public function createRentalRequestFromApi(array $data, User $user): RentalRequest
    {
        return DB::transaction(function () use ($data, $user) {
            \Log::info('🔧 API RentalRequest creation started', [
                'user_id' => $user->id,
                'items_count' => count($data['items'] ?? [])
            ]);

            $requestData = $this->prepareRequestData($data, $user);
            $rentalRequest = RentalRequest::create($requestData);

            // Создаем items с метаданными
            foreach ($data['items'] as $itemData) {
                \Log::debug('🔧 API Creating item with metadata', [
                    'category_id' => $itemData['category_id'],
                    'specifications_count' => count($itemData['specifications'] ?? []),
                    'metadata_count' => count($itemData['custom_specs_metadata'] ?? [])
                ]);

                $rentalRequest->items()->create([
                    'rental_request_id' => $rentalRequest->id,
                    'category_id' => $itemData['category_id'],
                    'quantity' => $itemData['quantity'],
                    'hourly_rate' => $itemData['hourly_rate'] ?? null,
                    'use_individual_conditions' => $itemData['use_individual_conditions'] ?? false,
                    'individual_conditions' => $itemData['individual_conditions'] ?? null,
                    'specifications' => $itemData['specifications'] ?? null,
                    // ⚠️ КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Сохраняем метаданные
                    'custom_specs_metadata' => $itemData['custom_specs_metadata'] ?? null,
                    'calculated_price' => 0
                ]);
            }

            $rentalRequest->load('items');
            $rentalRequest->calculateBudget();

            \Log::info('✅ API Rental request created successfully', [
                'request_id' => $rentalRequest->id,
                'total_budget' => $rentalRequest->total_budget,
                'items_count' => $rentalRequest->items->count(),
                'items_with_metadata' => $rentalRequest->items->filter(fn($item) => !empty($item->custom_specs_metadata))->count()
            ]);

            return $rentalRequest->refresh();
        });
    }
}
