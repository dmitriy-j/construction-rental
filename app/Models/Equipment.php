<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;



class Equipment extends Model
{

    use HasFactory;

    protected $fillable = [
    'title', 'slug', 'description', 'company_id', 'category_id',
    'location_id', 'brand', 'model', 'year', 'hours_worked',
    'rating', 'is_featured', 'is_approved'
    ];


    protected $casts = [
        'specifications' => 'array',
        'working_hours_per_day' => 'integer', // Добавить новое поле
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function specifications()
    {
        return $this->hasMany(Specification::class);
    }

    public function rentalTerms()
    {
        return $this->hasMany(EquipmentRentalTerm::class);
    }

    public function images()
    {
        return $this->hasMany(EquipmentImage::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function getMainImageAttribute()
    {
        return $this->images()->where('is_main', true)->first()
            ?? $this->images()->first();
    }
    public function availabilities()
    {
        return $this->hasMany(EquipmentAvailability::class);
    }

    public function hasActiveRentalTerms(): bool
    {
        return $this->rentalTerms()->exists();
    }

    public function getAvailabilityStatusAttribute(): string
    {
        $activeBookings = EquipmentAvailability::where('equipment_id', $this->id)
            ->where('date', '>=', now()->format('Y-m-d'))
            ->where(function($query) {
                $query->where('status', 'booked')
                    ->orWhere('status', 'maintenance')
                    ->orWhere(function($q) {
                        $q->where('status', 'temp_reserve')
                            ->where('expires_at', '>', now());
                    });
            })
            ->exists();

        return $activeBookings ? 'unavailable' : 'available';
    }
}
