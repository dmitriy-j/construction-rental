<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarkupDailyStat extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'markup_daily_stats';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'stat_date',
        'platform_markup_id',
        'applications_count',
        'daily_revenue',
        'calculation_errors',
        'average_calculation_time',
        'peak_applications_hour',
        'peak_revenue_hour',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'stat_date' => 'date',
        'applications_count' => 'integer',
        'daily_revenue' => 'decimal:2',
        'calculation_errors' => 'integer',
        'average_calculation_time' => 'decimal:2',
        'peak_applications_hour' => 'integer',
        'peak_revenue_hour' => 'decimal:2',
    ];

    /**
     * Get the markup that owns the daily stat.
     */
    public function markup(): BelongsTo
    {
        return $this->belongsTo(PlatformMarkup::class, 'platform_markup_id');
    }

    /**
     * Scope for specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('stat_date', $date);
    }

    /**
     * Scope for date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('stat_date', [$startDate, $endDate]);
    }

    /**
     * Scope for recent days
     */
    public function scopeRecentDays($query, $days = 7)
    {
        return $query->where('stat_date', '>=', now()->subDays($days));
    }

    /**
     * Get success rate for the day
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->applications_count === 0) {
            return 0.0;
        }

        $successfulApplications = $this->applications_count - $this->calculation_errors;
        return round(($successfulApplications / $this->applications_count) * 100, 2);
    }

    /**
     * Check if it was a peak day
     */
    public function getIsPeakDayAttribute(): bool
    {
        return $this->applications_count > 50; // Пороговое значение для пикового дня
    }

    /**
     * Get formatted revenue
     */
    public function getFormattedRevenueAttribute(): string
    {
        return number_format($this->daily_revenue, 2) . ' ₽';
    }

    /**
     * Get revenue per application
     */
    public function getRevenuePerApplicationAttribute(): float
    {
        if ($this->applications_count === 0) {
            return 0.0;
        }

        return round($this->daily_revenue / $this->applications_count, 2);
    }
}
