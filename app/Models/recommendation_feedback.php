<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecommendationFeedback extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'recommendation_feedback';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'template_id',
        'request_id',
        'applied',
        'converted',
        'score'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'applied' => 'boolean',
        'converted' => 'boolean',
        'score' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the feedback.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the template that the feedback is for.
     */
    public function template()
    {
        return $this->belongsTo(ProposalTemplate::class, 'template_id');
    }

    /**
     * Get the rental request that the feedback is for.
     */
    public function rentalRequest()
    {
        return $this->belongsTo(RentalRequest::class, 'request_id');
    }

    /**
     * Scope applied recommendations.
     */
    public function scopeApplied($query)
    {
        return $query->where('applied', true);
    }

    /**
     * Scope converted recommendations.
     */
    public function scopeConverted($query)
    {
        return $query->where('converted', true);
    }

    /**
     * Scope by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope by template.
     */
    public function scopeByTemplate($query, $templateId)
    {
        return $query->where('template_id', $templateId);
    }

    /**
     * Scope with high score.
     */
    public function scopeHighScore($query, $threshold = 70)
    {
        return $query->where('score', '>=', $threshold);
    }

    /**
     * Scope recent feedback.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Calculate application rate for a user or template.
     */
    public static function calculateApplicationRate($userId = null, $templateId = null)
    {
        $query = static::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($templateId) {
            $query->where('template_id', $templateId);
        }

        $total = $query->count();
        $applied = $query->where('applied', true)->count();

        return $total > 0 ? round(($applied / $total) * 100, 2) : 0;
    }

    /**
     * Calculate conversion rate for applied recommendations.
     */
    public static function calculateConversionRate($userId = null, $templateId = null)
    {
        $query = static::where('applied', true);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($templateId) {
            $query->where('template_id', $templateId);
        }

        $applied = $query->count();
        $converted = $query->where('converted', true)->count();

        return $applied > 0 ? round(($converted / $applied) * 100, 2) : 0;
    }

    /**
     * Get average score for user or template.
     */
    public static function getAverageScore($userId = null, $templateId = null)
    {
        $query = static::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($templateId) {
            $query->where('template_id', $templateId);
        }

        return $query->avg('score') ?? 0;
    }

    /**
     * Get template performance statistics.
     */
    public static function getTemplateStats($templateId, $days = 30)
    {
        return static::byTemplate($templateId)
            ->recent($days)
            ->selectRaw('
                COUNT(*) as total_recommendations,
                SUM(CASE WHEN applied = true THEN 1 ELSE 0 END) as applied_count,
                SUM(CASE WHEN converted = true THEN 1 ELSE 0 END) as converted_count,
                AVG(score) as average_score
            ')
            ->first();
    }

    /**
     * Get user recommendation statistics.
     */
    public static function getUserStats($userId, $days = 30)
    {
        return static::byUser($userId)
            ->recent($days)
            ->selectRaw('
                COUNT(*) as total_recommendations,
                SUM(CASE WHEN applied = true THEN 1 ELSE 0 END) as applied_count,
                SUM(CASE WHEN converted = true THEN 1 ELSE 0 END) as converted_count,
                AVG(score) as average_score
            ')
            ->first();
    }

    /**
     * Log new feedback entry.
     */
    public static function logFeedback($data)
    {
        return static::create([
            'user_id' => $data['user_id'],
            'template_id' => $data['template_id'],
            'request_id' => $data['request_id'],
            'applied' => $data['applied'],
            'converted' => $data['converted'] ?? false,
            'score' => $data['score']
        ]);
    }

    /**
     * Check if feedback already exists for this combination.
     */
    public static function feedbackExists($userId, $templateId, $requestId)
    {
        return static::where('user_id', $userId)
            ->where('template_id', $templateId)
            ->where('request_id', $requestId)
            ->exists();
    }

    /**
     * Get top performing templates for user.
     */
    public static function getTopTemplates($userId, $limit = 5)
    {
        return static::byUser($userId)
            ->selectRaw('
                template_id,
                COUNT(*) as total_recommendations,
                SUM(CASE WHEN applied = true THEN 1 ELSE 0 END) as applied_count,
                SUM(CASE WHEN converted = true THEN 1 ELSE 0 END) as converted_count,
                AVG(score) as average_score,
                ROUND(SUM(CASE WHEN applied = true THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as application_rate,
                ROUND(SUM(CASE WHEN converted = true THEN 1 ELSE 0 END) * 100.0 / SUM(CASE WHEN applied = true THEN 1 ELSE 0 END), 2) as conversion_rate
            ')
            ->groupBy('template_id')
            ->having('total_recommendations', '>=', 3) // Минимум 3 рекомендации
            ->orderByDesc('conversion_rate')
            ->orderByDesc('application_rate')
            ->limit($limit)
            ->get();
    }

    /**
     * Get improvement suggestions based on feedback.
     */
    public function getImprovementSuggestions()
    {
        $suggestions = [];

        if ($this->score < 50 && !$this->applied) {
            $suggestions[] = 'Низкий балл релевантности. Рассмотрите изменение цены или условий шаблона.';
        }

        if ($this->applied && !$this->converted) {
            $suggestions[] = 'Шаблон применялся но не конвертировался. Возможно, требуется улучшить предложение.';
        }

        if ($this->score >= 80 && !$this->applied) {
            $suggestions[] = 'Высокий балл но не применен. Проверьте timing или дополнительные условия.';
        }

        return $suggestions;
    }

    /**
     * Accessor for performance rating.
     */
    public function getPerformanceRatingAttribute()
    {
        if (!$this->applied) return 'not_applied';

        if ($this->converted) {
            return $this->score >= 80 ? 'excellent' : 'good';
        }

        return $this->score >= 70 ? 'needs_improvement' : 'poor';
    }

    /**
     * Accessor for performance color.
     */
    public function getPerformanceColorAttribute()
    {
        return match($this->performance_rating) {
            'excellent' => 'success',
            'good' => 'info',
            'needs_improvement' => 'warning',
            'poor' => 'danger',
            default => 'secondary'
        };
    }
}
