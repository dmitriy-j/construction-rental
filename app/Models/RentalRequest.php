<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalRequest extends Model
{
    protected $fillable = [
        'user_id', 'company_id', 'title', 'description', 'category_id',
        'desired_specifications', 'rental_period_start', 'rental_period_end',
        'budget_from', 'budget_to', 'location_id', 'delivery_required',
        'status', 'expires_at'
    ];

    protected $casts = [
        'desired_specifications' => 'array',
        'rental_period_start' => 'date',
        'rental_period_end' => 'date',
        'budget_from' => 'float',
        'budget_to' => 'float',
        'delivery_required' => 'boolean',
        'expires_at' => 'datetime'
    ];

    public function getStatusTextAttribute()
    {
        $statuses = [
            'draft' => 'Черновик',
            'active' => 'Активна',
            'processing' => 'В процессе',
            'completed' => 'Завершена',
            'cancelled' => 'Отменена'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'draft' => 'secondary',
            'active' => 'success',
            'processing' => 'warning',
            'completed' => 'info',
            'cancelled' => 'danger'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getDescriptionShortAttribute()
    {
        return Str::limit($this->description, 100);
    }

    public function getViewUrlAttribute()
    {
        if (auth()->user()->is_lessee) {
            return route('lessee.rental-requests.show', $this->id);
        } else {
            return route('lessor.rental-requests.show', $this->id);
        }
    }

    // Отношения
    public function user() { return $this->belongsTo(User::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function location() { return $this->belongsTo(Location::class); }
    public function responses() { return $this->hasMany(RentalRequestResponse::class); }
    public function acceptedResponse() { return $this->hasOne(RentalRequestResponse::class)->where('status', 'accepted'); }

    // Scopes
    public function scopeActive($query) { return $query->where('status', 'active'); }
    public function scopeForCategory($query, $categoryId) { return $query->where('category_id', $categoryId); }
    public function scopeNearLocation($query, $locationId, $radiusKm = 50) { /* логика поиска по радиусу */ }

}


