<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountTier extends Model
{
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
