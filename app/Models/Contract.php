<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'number',
        'description',
        'payment_type',
        'documentation_deadline',
        'payment_deadline',
        'penalty_rate',
        'file_path',
        'start_date',
        'end_date',
        'is_active'
    ];

    protected $casts = [
        'documentation_deadline' => 'integer',
        'payment_deadline' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function rentalConditions(): HasMany
    {
        return $this->hasMany(RentalCondition::class);
    }
}
