<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompletionAct extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'order_id', 'act_date', 'service_start_date', 'service_end_date', 'total_hours', 'total_downtime',
        'penalty_amount', 'total_amount', 'prepayment_amount',
        'final_amount', 'act_file_path', 'status'
    ];

    protected $casts = [
    'act_date' => 'datetime',
    'service_start_date' => 'datetime',
    'service_end_date' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
