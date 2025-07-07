<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DiscountTier extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'company_id',
        'min_turnover',
        'discount_percent'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
