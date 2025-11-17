<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarkupTemplateUsage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'markup_template_usage';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'template_id',
        'user_id',
        'resulting_markup_id',
        'applied_data',
        'customizations',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'applied_data' => 'array',
        'customizations' => 'array',
    ];

    /**
     * Get the template that was used.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(MarkupTemplate::class, 'template_id');
    }

    /**
     * Get the user who used the template.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the resulting markup.
     */
    public function resultingMarkup(): BelongsTo
    {
        return $this->belongsTo(PlatformMarkup::class, 'resulting_markup_id');
    }

    /**
     * Scope for recent usage
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for specific user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get customization summary
     */
    public function getCustomizationSummaryAttribute(): string
    {
        if (empty($this->customizations)) {
            return 'Без изменений';
        }

        $changes = [];
        foreach ($this->customizations as $field => $change) {
            $changes[] = "{$field}: {$change['from']} → {$change['to']}";
        }

        return implode(', ', $changes);
    }

    /**
     * Check if usage resulted in a markup
     */
    public function getResultedInMarkupAttribute(): bool
    {
        return !is_null($this->resulting_markup_id);
    }
}
