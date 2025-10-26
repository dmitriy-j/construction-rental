<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentStatusLog extends Model
{
    protected $fillable = [
        'equipment_id',
        'status',
        'changed_by',
        'notes',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
