<?php
// app/Models/BankDetailsAudit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankDetailsAudit extends Model
{
    protected $table = 'bank_details_audit';

    protected $fillable = [
        'company_id',
        'changed_by',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
