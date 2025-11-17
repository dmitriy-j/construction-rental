<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarkupStatistic extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'markup_statistics';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'platform_markup_id',
        'application_count',
        'total_revenue',
        'average_revenue_per_application',
        'success_rate',
        'average_response_time',
        'last_application_date',
        'first_application_date',
        'applications_by_entity_type',
        'revenue_by_period',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'application_count' => 'integer',
        'total_revenue' => 'decimal:2',
        'average_revenue_per_application' => 'decimal:2',
        'success_rate' => 'decimal:2',
        'average_response_time' => 'decimal:2',
        'last_application_date' => 'date',
        'first_application_date' => 'date',
        'applications_by_entity_type' => 'array',
        'revenue_by_period' => 'array',
    ];

    /**
     * Get the markup that owns the statistic.
     */
    public function markup(): BelongsTo
    {
        return $this->belongsTo(PlatformMarkup::class, 'platform_markup_id');
    }

    /**
     * Scope for active markups statistics
     */
    public function scopeForActiveMarkups($query)
    {
        return $query->whereHas('markup', function ($q) {
            $q->where('is_active', true);
        });
    }

    /**
     * Scope for recent statistics (last 30 days)
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('last_application_date', '>=', now()->subDays($days));
    }

    /**
     * Scope for top performing markups
     */
    public function scopeTopPerforming($query, $limit = 10)
    {
        return $query->orderBy('total_revenue', 'desc')
                    ->orderBy('application_count', 'desc')
                    ->limit($limit);
    }

    /**
     * Calculate efficiency score
     */
    public function getEfficiencyScoreAttribute(): float
    {
        // Комплексная оценка эффективности на основе нескольких метрик
        $revenueScore = min($this->total_revenue / 10000, 1.0); // Нормализация до 1.0
        $successScore = $this->success_rate / 100;
        $frequencyScore = min($this->application_count / 100, 1.0);

        return round(($revenueScore * 0.4 + $successScore * 0.3 + $frequencyScore * 0.3) * 100, 2);
    }

    /**
     * Get applications count by entity type
     */
    public function getApplicationsByEntity(string $entityType): int
    {
        return $this->applications_by_entity_type[$entityType] ?? 0;
    }

    /**
     * Update statistics after new application
     */
    public function recordApplication(float $revenue, string $entityType, float $responseTime = null): void
    {
        $this->application_count++;
        $this->total_revenue += $revenue;
        $this->average_revenue_per_application = $this->total_revenue / $this->application_count;

        // Update entity type distribution
        $applicationsByEntity = $this->applications_by_entity_type ?? [];
        $applicationsByEntity[$entityType] = ($applicationsByEntity[$entityType] ?? 0) + 1;
        $this->applications_by_entity_type = $applicationsByEntity;

        // Update response time
        if ($responseTime) {
            $currentTotalTime = $this->average_response_time * max(0, $this->application_count - 1);
            $this->average_response_time = ($currentTotalTime + $responseTime) / $this->application_count;
        }

        // Update dates
        $now = now();
        if (!$this->first_application_date) {
            $this->first_application_date = $now;
        }
        $this->last_application_date = $now;

        $this->save();
    }

    /**
     * Get revenue growth percentage
     */
    public function getRevenueGrowthAttribute(): float
    {
        // Здесь должна быть логика сравнения с предыдущим периодом
        // Временно возвращаем 0
        return 0.0;
    }

    /**
     * Check if markup is frequently used
     */
    public function getIsFrequentlyUsedAttribute(): bool
    {
        return $this->application_count > 10; // Пороговое значение
    }

    /**
     * Get formatted revenue
     */
    public function getFormattedRevenueAttribute(): string
    {
        return number_format($this->total_revenue, 2) . ' ₽';
    }

    /**
     * Get formatted average revenue
     */
    public function getFormattedAverageRevenueAttribute(): string
    {
        return number_format($this->average_revenue_per_application, 2) . ' ₽';
    }
}
