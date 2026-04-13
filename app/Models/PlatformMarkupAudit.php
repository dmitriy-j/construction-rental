<?php
// app/Models/PlatformMarkupAudit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformMarkupAudit extends Model
{
    protected $fillable = [
        'platform_markup_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'reason',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function markup(): BelongsTo
    {
        return $this->belongsTo(PlatformMarkup::class, 'platform_markup_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получение читаемого названия действия
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'created' => 'Создана',
            'updated' => 'Обновлена',
            'deleted' => 'Удалена',
            default => $this->action,
        };
    }

    /**
     * Получение форматированных изменений
     */
    public function getFormattedChangesAttribute(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;

            if ($oldValue != $newValue) {
                $changes[] = [
                    'field' => $this->getFieldLabel($key),
                    'from' => $this->formatValue($key, $oldValue),
                    'to' => $this->formatValue($key, $newValue),
                ];
            }
        }

        return $changes;
    }

    private function getFieldLabel(string $field): string
    {
        return match($field) {
            'value' => 'Значение',
            'type' => 'Тип наценки',
            'entity_type' => 'Контекст',
            'calculation_type' => 'Тип расчета',
            'is_active' => 'Статус',
            'valid_from' => 'Действует с',
            'valid_to' => 'Действует до',
            default => $field,
        };
    }

    private function formatValue(string $field, $value): string
    {
        if ($value === null) {
            return 'не установлено';
        }

        if ($field === 'is_active') {
            return $value ? 'активна' : 'неактивна';
        }

        return (string) $value;
    }

    /**
     * Get the statistics for the markup.
     */
    public function statistics(): HasOne
    {
        return $this->hasOne(MarkupStatistic::class, 'platform_markup_id');
    }

    /**
     * Get the daily stats for the markup.
     */
    public function dailyStats(): HasMany
    {
        return $this->hasMany(MarkupDailyStat::class, 'platform_markup_id');
    }

    /**
     * Get the template usage that created this markup.
     */
    public function templateUsage(): HasOne
    {
        return $this->hasOne(MarkupTemplateUsage::class, 'resulting_markup_id');
    }
}
