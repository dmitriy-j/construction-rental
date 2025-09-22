<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaybillStatusHistory extends Model
{
    protected $fillable = [
        'waybill_id',
        'old_status',
        'new_status',
        'changed_by',
        'notes',
    ];

    public function waybill(): BelongsTo
    {
        return $this->belongsTo(Waybill::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
