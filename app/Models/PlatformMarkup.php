<?php
// app/Models/PlatformMarkup.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PlatformMarkup extends Model
{
    protected $fillable = [
        'platform_id',
        'markupable_id',
        'markupable_type',
        'type',
        'entity_type',
        'calculation_type',
        'value',
        'rules',
        'is_active',
        'valid_from',
        'valid_to',
        'priority',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'rules' => 'array',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'priority' => 'integer',
    ];

    public function markupable(): MorphTo
    {
        return $this->morphTo();
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    public function audits()
    {
        return $this->hasMany(PlatformMarkupAudit::class);
    }

    /**
     * Scope для активных наценок
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('valid_to')
                  ->orWhere('valid_to', '>=', now());
            });
    }

    /**
     * Scope для наценок по типу сущности
     */
    public function scopeForEntityType($query, $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope для наценок по типу привязки
     */
    public function scopeForMarkupable($query, $markupableType, $markupableId = null)
    {
        $query->where('markupable_type', $markupableType);

        if ($markupableId) {
            $query->where('markupable_id', $markupableId);
        }

        return $query;
    }

    /**
     * Проверка, действует ли наценка в данный момент
     */
    public function isCurrentlyValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_to && $this->valid_to->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Получение читаемого названия типа наценки
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'fixed' => 'Фиксированная',
            'percent' => 'Процентная',
            'tiered' => 'Ступенчатая',
            'combined' => 'Комбинированная',
            'seasonal' => 'Сезонная',
            default => $this->type,
        };
    }

    /**
     * Получение читаемого названия контекста
     */
    public function getEntityTypeLabelAttribute(): string
    {
        return match($this->entity_type) {
            'order' => 'Заказы',
            'rental_request' => 'Заявки',
            'proposal' => 'Предложения',
            default => $this->entity_type,
        };
    }

    /**
     * Получение названия привязанной сущности
     */
    public function getMarkupableNameAttribute(): ?string
    {
        if (!$this->markupable_type) {
            return 'Общая наценка';
        }

        if (!$this->markupable) {
            return 'Сущность не найдена';
        }

        return match($this->markupable_type) {
            Equipment::class => $this->markupable->title,
            EquipmentCategory::class => $this->markupable->name,
            Company::class => $this->markupable->legal_name,
            default => 'Неизвестная сущность',
        };
    }

    /**
     * Создание записи аудита
     */
    public function logAudit(string $action, ?array $oldValues = null, ?array $newValues = null, ?string $reason = null): void
    {
        $this->audits()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
        ]);
    }

    /**
     * Scope для поиска по контексту (приоритет: оборудование → категория → компания → общая)
     */
    public function scopeForContext($query, ?int $equipmentId, ?int $categoryId, ?int $companyId, ?int $lesseeCompanyId)
    {
        return $query->where(function($q) use ($equipmentId, $categoryId, $companyId, $lesseeCompanyId) {
            // Оборудование (высший приоритет)
            if ($equipmentId) {
                $q->orWhere(function($subQ) use ($equipmentId) {
                    $subQ->where('markupable_type', Equipment::class)
                         ->where('markupable_id', $equipmentId);
                });
            }

            // Категория оборудования
            if ($categoryId) {
                $q->orWhere(function($subQ) use ($categoryId) {
                    $subQ->where('markupable_type', EquipmentCategory::class)
                         ->where('markupable_id', $categoryId);
                });
            }

            // Компания арендатора
            if ($lesseeCompanyId) {
                $q->orWhere(function($subQ) use ($lesseeCompanyId) {
                    $subQ->where('markupable_type', Company::class)
                         ->where('markupable_id', $lesseeCompanyId);
                });
            }

            // Общая наценка (низший приоритет)
            $q->orWhere(function($subQ) {
                $subQ->whereNull('markupable_type')
                     ->whereNull('markupable_id');
            });
        });
    }

    /**
     * Получение источника наценки для логов
     */
    public function getMarkupSource(): string
    {
        if (!$this->markupable_type) {
            return 'platform_general';
        }

        return $this->markupable_type . ':' . $this->markupable_id;
    }

    /**
     * Проверка, действует ли наценка в указанное время
     */
    public function isValidAt(?\Carbon\Carbon $date = null): bool
    {
        $date = $date ?: now();

        if (!$this->is_active) {
            return false;
        }

        if ($this->valid_from && $this->valid_from->gt($date)) {
            return false;
        }

        if ($this->valid_to && $this->valid_to->lt($date)) {
            return false;
        }

        return true;
    }

    /**
     * Атрибут для отображения периода действия
     */
    protected function validityPeriod(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->valid_from && !$this->valid_to) {
                    return 'Постоянно';
                }

                $from = $this->valid_from ? $this->valid_from->format('d.m.Y') : '∞';
                $to = $this->valid_to ? $this->valid_to->format('d.m.Y') : '∞';

                return "{$from} - {$to}";
            }
        );
    }
}
