<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarkupTemplate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'markup_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'category',
        'markupable_type',
        'entity_type',
        'type',
        'calculation_type',
        'value',
        'priority',
        'rules',
        'is_active',
        'is_system',
        'usage_count',
        'tags',
        'version',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:2',
        'priority' => 'integer',
        'rules' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'usage_count' => 'integer',
        'tags' => 'array',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Get the user who created the template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the template.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the usage history for the template.
     */
    public function usageHistory(): HasMany
    {
        return $this->hasMany(MarkupTemplateUsage::class, 'template_id');
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for system templates
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope for user templates
     */
    public function scopeUser($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for popular templates
     */
    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')
                    ->orderBy('updated_at', 'desc')
                    ->limit($limit);
    }

    /**
     * Scope for search
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhereJsonContains('tags', $search);
        });
    }

    /**
     * Create a markup from this template
     */
    public function createMarkup(array $overrides = [], User $user = null): PlatformMarkup
    {
        $markupData = [
            'platform_id' => $overrides['platform_id'] ?? 1, // Основная платформа
            'markupable_type' => $overrides['markupable_type'] ?? $this->markupable_type,
            'markupable_id' => $overrides['markupable_id'] ?? null,
            'entity_type' => $overrides['entity_type'] ?? $this->entity_type,
            'type' => $overrides['type'] ?? $this->type,
            'calculation_type' => $overrides['calculation_type'] ?? $this->calculation_type,
            'value' => $overrides['value'] ?? $this->value,
            'priority' => $overrides['priority'] ?? $this->priority,
            'rules' => $overrides['rules'] ?? $this->rules,
            'is_active' => $overrides['is_active'] ?? $this->is_active,
            'valid_from' => $overrides['valid_from'] ?? null,
            'valid_to' => $overrides['valid_to'] ?? null,
        ];

        $markup = PlatformMarkup::create($markupData);

        // Record usage
        $this->recordUsage($user, $markup, $overrides);

        return $markup;
    }

    /**
     * Record template usage
     */
    public function recordUsage(?User $user, ?PlatformMarkup $markup, array $customizations = []): void
    {
        $this->usage_count++;
        $this->save();

        if ($user) {
            MarkupTemplateUsage::create([
                'template_id' => $this->id,
                'user_id' => $user->id,
                'resulting_markup_id' => $markup?->id,
                'applied_data' => $customizations,
                'customizations' => $this->getCustomizations($customizations),
            ]);
        }
    }

    /**
     * Get customizations compared to template
     */
    protected function getCustomizations(array $overrides): array
    {
        $customizations = [];

        $fields = ['markupable_type', 'entity_type', 'type', 'calculation_type', 'value', 'priority', 'rules'];

        foreach ($fields as $field) {
            if (isset($overrides[$field]) && $overrides[$field] != $this->$field) {
                $customizations[$field] = [
                    'from' => $this->$field,
                    'to' => $overrides[$field],
                ];
            }
        }

        return $customizations;
    }

    /**
     * Check if template can be deleted
     */
    public function getCanBeDeletedAttribute(): bool
    {
        return !$this->is_system && $this->usage_count === 0;
    }

    /**
     * Get formatted value
     */
    public function getFormattedValueAttribute(): string
    {
        $suffix = $this->type === 'percent' ? '%' : ' ₽/час';
        return number_format($this->value, 2) . $suffix;
    }

    /**
     * Get template description with details
     */
    public function getDetailedDescriptionAttribute(): string
    {
        $typeLabels = [
            'fixed' => 'Фиксированная',
            'percent' => 'Процентная',
            'tiered' => 'Ступенчатая',
            'combined' => 'Комбинированная',
            'seasonal' => 'Сезонная',
        ];

        $entityLabels = [
            'order' => 'Заказы',
            'rental_request' => 'Заявки',
            'proposal' => 'Предложения',
        ];

        $type = $typeLabels[$this->type] ?? $this->type;
        $entity = $entityLabels[$this->entity_type] ?? $this->entity_type;

        return "{$type} наценка для {$entity} - {$this->formatted_value}";
    }

    /**
     * Duplicate template
     */
    public function duplicate(User $user): MarkupTemplate
    {
        $duplicate = $this->replicate();
        $duplicate->name = $this->name . ' (копия)';
        $duplicate->is_system = false;
        $duplicate->usage_count = 0;
        $duplicate->created_by = $user->id;
        $duplicate->updated_by = $user->id;
        $duplicate->save();

        return $duplicate;
    }
}
