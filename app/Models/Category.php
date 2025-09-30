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

    public function rentalRequestItems(): HasMany
    {
        return $this->hasMany(RentalRequestItem::class, 'category_id');
    }

    public function rentalRequests()
    {
        return $this->hasManyThrough(
            RentalRequest::class,
            RentalRequestItem::class,
            'category_id', // Внешний ключ в rental_request_items
            'id', // Внешний ключ в rental_requests
            'id', // Локальный ключ в categories
            'rental_request_id' // Локальный ключ в rental_request_items
        );
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

    public function scopeActive($query)
    {
        // Assuming you have an 'is_active' column on your categories table
        return $query->where('is_active', true);

        // If the column is named differently, e.g., 'status', adjust accordingly:
        // return $query->where('status', 'active');
    }
}
