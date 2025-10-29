<?php
// app/Models/EquipmentImport.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'file_name',
        'original_name',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'errors',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Scope для фильтрации
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return ($this->processed_rows / $this->total_rows) * 100;
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(array $results): void
    {
        $this->update([
            'status' => 'completed',
            'processed_rows' => $results['processed'],
            'successful_rows' => $results['successful'],
            'failed_rows' => $results['failed'],
            'errors' => $results['errors'],
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }
}
