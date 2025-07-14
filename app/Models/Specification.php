<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specification extends Model
{
    use HasFactory;

    protected $table = 'equipment_specifications';

    protected $fillable = [
        'equipment_id',
        'key',
        'value',
        'weight',
        'length',
        'width',
        'height'
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
