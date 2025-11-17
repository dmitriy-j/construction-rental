<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarkupAggregatedStat extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'markup_aggregated_stats';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'period_type',
        'period_start',
        'period_end',
        'total_applications',
        'total_revenue',
        'active_markups_count',
        'new_markups_count',
        'expired_markups_count',
        'applications_by_type',
        'revenue_by_type',
        'applications_by_entity',
        'average_response_time',
        'cache_hit_rate',
        'total_errors',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_applications' => 'integer',
        'total_revenue' => 'decimal:2',
        'active_markups_count' => 'integer',
        'new_markups_count' => 'integer',
        'expired_markups_count' => 'integer',
        'applications_by_type' => 'array',
        'revenue_by_type' => 'array',
        'applications_by_entity' => 'array',
        'average_response_time' => 'decimal:2',
        'cache_hit_rate' => 'decimal:2',
        'total_errors' => 'integer',
    ];

    /**
     * Scope for specific period type
     */
    public function scopeForPeriodType($query, $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * Scope for date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
                    ->where('period_end', '<=', $endDate);
    }

    /**
     * Scope for recent periods
     */
    public function scopeRecent($query, $periods = 12)
    {
        return $query->orderBy('period_start', 'desc')
                    ->limit($periods);
    }

    /**
     * Get error rate
     */
    public function getErrorRateAttribute(): float
    {
        if ($this->total_applications === 0) {
            return 0.0;
        }

        return round(($this->total_errors / $this->total_applications) * 100, 2);
    }

    /**
     * Get revenue per application
     */
    public function getRevenuePerApplicationAttribute(): float
    {
        if ($this->total_applications === 0) {
            return 0.0;
        }

        return round($this->total_revenue / $this->total_applications, 2);
    }

    /**
     * Get applications by markup type
     */
    public function getApplicationsByMarkupType(string $type): int
    {
        return $this->applications_by_type[$type] ?? 0;
    }

    /**
     * Get revenue by markup type
     */
    public function getRevenueByMarkupType(string $type): float
    {
        return $this->revenue_by_type[$type] ?? 0.0;
    }

    /**
     * Get applications by entity type
     */
    public function getApplicationsByEntityType(string $entityType): int
    {
        return $this->applications_by_entity[$entityType] ?? 0;
    }

    /**
     * Get growth rate compared to previous period
     */
    public function getGrowthRateAttribute(): ?float
    {
        $previous = static::where('period_type', $this->period_type)
                         ->where('period_end', '<', $this->period_start)
                         ->orderBy('period_end', 'desc')
                         ->first();

        if (!$previous || $previous->total_revenue == 0) {
            return null;
        }

        return round((($this->total_revenue - $previous->total_revenue) / $previous->total_revenue) * 100, 2);
    }

    /**
     * Get formatted revenue
     */
    public function getFormattedRevenueAttribute(): string
    {
        return number_format($this->total_revenue, 2) . ' ₽';
    }

    /**
     * Get period label
     */
    public function getPeriodLabelAttribute(): string
    {
        return match($this->period_type) {
            'daily' => $this->period_start->format('d.m.Y'),
            'weekly' => $this->period_start->format('d.m') . ' - ' . $this->period_end->format('d.m.Y'),
            'monthly' => $this->period_start->format('m.Y'),
            'yearly' => $this->period_start->format('Y'),
            default => $this->period_start->format('d.m.Y') . ' - ' . $this->period_end->format('d.m.Y')
        };
    }
}
