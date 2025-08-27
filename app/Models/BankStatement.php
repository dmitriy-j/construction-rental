<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatement extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'bank_name',
        'transactions_count',
        'processed_count',
        'error_count',
        'status',
        'processed_by',
        'processed_at',
        'error_log'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted(int $processed, int $errors, ?string $log = null): void
    {
        $this->update([
            'status' => 'completed',
            'processed_count' => $processed,
            'error_count' => $errors,
            'error_log' => $log,
            'processed_at' => now()
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_log' => $error,
            'processed_at' => now()
        ]);
    }
}
