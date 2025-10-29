<?php
// app/Models/ProposalTemplate.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'category_id',
        'proposed_price',
        'response_time',
        'message',
        'price_breakdown',
        'additional_terms',
        'usage_count',
        'success_rate',
        'is_active',
        'conditions',
        // 🔥 ВАЖНО: Добавляем все поля A/B тестирования
        'is_ab_test',
        'ab_test_variants',
        'test_distribution',
        'test_metric',
        'ab_test_started_at',
        'ab_test_winner',
        'ab_test_status'
    ];

    protected $casts = [
        'conditions' => 'array',
        'price_breakdown' => 'array',
        'additional_terms' => 'array',
        'proposed_price' => 'decimal:2',
        'success_rate' => 'decimal:2',
        'is_active' => 'boolean',
        // 🔥 ВАЖНО: Добавляем casts для A/B тестирования
        'is_ab_test' => 'boolean',
        'ab_test_variants' => 'array',
        'ab_test_started_at' => 'datetime'
    ];

    // Константы для статусов A/B теста
    const AB_TEST_ACTIVE = 'active';
    const AB_TEST_PAUSED = 'paused';
    const AB_TEST_COMPLETED = 'completed';

    // Константы для метрик
    const METRIC_CONVERSION = 'conversion';
    const METRIC_PRICE = 'price';
    const METRIC_SPEED = 'speed';

    // Отношения
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // 🔥 Новые отношения для статистики A/B тестов
    public function abTestStats()
    {
        return $this->hasMany(ProposalTemplateAbTestStat::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // 🔥 Новые scopes для A/B тестов
    public function scopeWithActiveAbTests($query)
    {
        return $query->where('is_ab_test', true)
                    ->where('ab_test_status', self::AB_TEST_ACTIVE);
    }

    // Методы
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    public function updateSuccessRate($isSuccessful)
    {
        $totalUses = $this->usage_count;
        $currentSuccessRate = $this->success_rate;

        if ($isSuccessful) {
            $newSuccessRate = (($currentSuccessRate * $totalUses) + 1) / ($totalUses + 1);
        } else {
            $newSuccessRate = ($currentSuccessRate * $totalUses) / ($totalUses + 1);
        }

        $this->success_rate = $newSuccessRate * 100;
        $this->save();
    }

    // 🔥 Новые методы для A/B тестирования
    public function getVariantForRequest($requestId)
    {
        if (!$this->is_ab_test || empty($this->ab_test_variants)) {
            return $this->getDefaultVariant();
        }

        $variantIndex = $this->calculateVariantIndex($requestId);
        return $this->ab_test_variants[$variantIndex] ?? $this->getDefaultVariant();
    }

    private function calculateVariantIndex($requestId)
    {
        // Простой алгоритм распределения на основе ID заявки
        return $requestId % count($this->ab_test_variants);
    }

    private function getDefaultVariant()
    {
        return [
            'name' => 'Основной вариант',
            'message' => $this->message,
            'proposed_price' => $this->proposed_price,
            'response_time' => $this->response_time,
            'additional_terms' => $this->additional_terms
        ];
    }

    public function startAbTest()
    {
        $this->update([
            'is_ab_test' => true,
            'ab_test_status' => self::AB_TEST_ACTIVE,
            'ab_test_started_at' => now()
        ]);
    }

    public function stopAbTest()
    {
        $this->update([
            'is_ab_test' => false,
            'ab_test_status' => self::AB_TEST_COMPLETED
        ]);
    }

    public function declareWinner($winnerIndex)
    {
        if (isset($this->ab_test_variants[$winnerIndex])) {
            $winner = $this->ab_test_variants[$winnerIndex];

            // Обновляем основной шаблон данными победителя
            $this->update([
                'message' => $winner['message'],
                'proposed_price' => $winner['proposed_price'],
                'response_time' => $winner['response_time'] ?? $this->response_time,
                'additional_terms' => $winner['additional_terms'] ?? $this->additional_terms,
                'ab_test_winner' => $winnerIndex
            ]);

            $this->stopAbTest();
        }
    }

    // Генерация данных для предложения на основе шаблона
    public function generateProposalData(RentalRequest $rentalRequest, Equipment $equipment = null)
    {
        $pricingService = app(\App\Services\PricingService::class);

        // 🔥 Получаем вариант для A/B теста если активен
        $variant = $this->getVariantForRequest($rentalRequest->id);

        $data = [
            'proposed_price' => $variant['proposed_price'] ?? $this->proposed_price,
            'proposed_quantity' => 1,
            'message' => $variant['message'] ?? $this->message,
            'additional_terms' => $variant['additional_terms'] ?? $this->additional_terms,
            'price_breakdown' => $this->price_breakdown,
            // 🔥 Добавляем информацию о варианте A/B теста
            'ab_test_variant' => $variant['name'] ?? null,
            'ab_test_variant_index' => $this->is_ab_test ? $this->calculateVariantIndex($rentalRequest->id) : null
        ];

        // Если передан equipment, можем рассчитать полную стоимость
        if ($equipment) {
            $workingHours = $this->calculateWorkingHours($rentalRequest);
            $pricingCalculation = $pricingService->calculateProposalPrice(
                $equipment,
                $rentalRequest,
                $data['proposed_price'],
                $workingHours
            );

            $data['price_breakdown'] = array_merge(
                $data['price_breakdown'] ?? [],
                $pricingCalculation
            );
        }

        return $data;
    }

    private function calculateWorkingHours(RentalRequest $request)
    {
        $start = \Carbon\Carbon::parse($request->rental_period_start);
        $end = \Carbon\Carbon::parse($request->rental_period_end);
        $days = $start->diffInDays($end) + 1;

        $rentalConditions = $request->rental_conditions ?? [];
        $shiftHours = $rentalConditions['hours_per_shift'] ?? 8;
        $shiftsPerDay = $rentalConditions['shifts_per_day'] ?? 1;

        return $days * $shiftHours * $shiftsPerDay;
    }
}
