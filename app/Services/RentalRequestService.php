<?php
// app/Services/RentalRequestService.php
namespace App\Services;

use App\Models\RentalRequest;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class RentalRequestService
{
    public function createRentalRequest(array $data, User $user): RentalRequest
    {
        \Log::info("Service received data:", $data);

        // Валидация обязательных полей
        $requiredFields = ['title', 'description', 'category_id', 'rental_period_start',
                        'rental_period_end', 'budget_from', 'budget_to', 'location_id'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Преобразование числовых полей
        $data['budget_from'] = (float) $data['budget_from'];
        $data['budget_to'] = (float) $data['budget_to'];
        $data['delivery_required'] = (bool) ($data['delivery_required'] ?? false);

        \Log::info("Service processed data:", $data);

        return DB::transaction(function () use ($data, $user) {
            $requestData = [
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'title' => $data['title'],
                'description' => $data['description'],
                'category_id' => $data['category_id'],
                'desired_specifications' => $data['specifications'] ?? null,
                'rental_period_start' => $data['rental_period_start'],
                'rental_period_end' => $data['rental_period_end'],
                'budget_from' => $data['budget_from'],
                'budget_to' => $data['budget_to'],
                'location_id' => $data['location_id'],
                'delivery_required' => $data['delivery_required'],
                'status' => 'active',
                'expires_at' => now()->addDays(30)
            ];

            \Log::info("Creating rental request with data:", $requestData);

            $request = RentalRequest::create($requestData);

            \Log::info("Rental request created successfully", ['id' => $request->id]);

            // Отправка уведомлений подходящим арендодателям
            try {
                app(RequestMatchingService::class)->notifyRelevantLessors($request);
            } catch (\Exception $e) {
                \Log::error("Error notifying lessors: " . $e->getMessage());
                // Не прерываем выполнение из-за ошибки уведомлений
            }

            return $request;
        });
    }

    public function updateRequestStatus(RentalRequest $request, string $status): void
    {
        $request->update(['status' => $status]);

        if ($status === 'completed') {
            // Архивирование завершенной заявки
            event(new RentalRequestCompleted($request));
        }
    }

    public function getActiveRequestsForLessor(User $lessor, array $filters = [])
    {
        $query = RentalRequest::active()
            ->with(['category', 'location', 'user.company'])
            ->whereDoesntHave('responses', function ($q) use ($lessor) {
                $q->where('lessor_id', $lessor->id);
            });

        // Применение фильтров
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (isset($filters['budget_max'])) {
            $query->where('budget_from', '<=', $filters['budget_max']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }
}
