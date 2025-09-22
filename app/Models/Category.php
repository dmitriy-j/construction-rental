<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    // Указываем правильное имя таблицы
    protected $table = 'equipment_categories';

     protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id'
    ];

    public function equipments(): HasMany
    {
        return $this->hasMany(Equipment::class, 'category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeChildren(Builder $query, int $parentId): Builder
    {
        return $query->where('parent_id', $parentId);
    }

    public function scopeWithEquipment(Builder $query): Builder
    {
        return $query->whereHas('equipments', function ($q) {
            $q->where('is_approved', true); // ← используем is_approved вместо is_active
        });
    }

    public function getIsRootAttribute(): bool
    {
        return is_null($this->parent_id);
    }

    public function getHasChildrenAttribute(): bool
    {
        return $this->children()->exists();
    }

    public function getHasEquipmentAttribute(): bool
    {
        return $this->equipments()->where('is_active', true)->exists();
    }
}
