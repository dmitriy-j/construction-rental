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
        'height',
    ];

    public function equipment()
    {
        \Log::debug('Full specification', [
            'spec' => $spec->log, // используем наш аксессор
        ]);

        return $this->belongsTo(Equipment::class);
    }

    public function getLogAttribute()
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->value,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
