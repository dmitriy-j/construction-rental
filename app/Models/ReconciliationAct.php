<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationAct extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'period_start',
        'period_end',
        'starting_balance',
        'ending_balance',
        'transactions',
        'confirmed_by_company',
        'confirmed_by_platform',
        'confirmed_at',
        'file_path',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'starting_balance' => 'decimal:2',
        'ending_balance' => 'decimal:2',
        'transactions' => 'array',
        'confirmed_by_company' => 'boolean',
        'confirmed_by_platform' => 'boolean',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Компания, для которой составлен акт сверки
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Генерация акта сверки
     */
    public static function generate(Company $company, $startDate, $endDate): self
    {
        // Получаем транзакции за период
        $transactions = TransactionEntry::where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('is_canceled', false)
            ->orderBy('created_at')
            ->get();

        // Начальный баланс (баланс на начало периода)
        $startingBalance = self::getBalanceAtDate($company, $startDate);

        // Конечный баланс (баланс на конец периода)
        $endingBalance = self::getBalanceAtDate($company, $endDate);

        $act = self::create([
            'company_id' => $company->id,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'starting_balance' => $startingBalance,
            'ending_balance' => $endingBalance,
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                    'amount' => $transaction->amount,
                    'type' => $transaction->type,
                    'purpose' => $transaction->purpose,
                    'description' => $transaction->description,
                    'balance_snapshot' => $transaction->balance_snapshot,
                ];
            })->toArray(),
            'confirmed_by_platform' => true,
        ]);

        return $act;
    }

    /**
     * Получение баланса компании на определенную дату
     */
    protected static function getBalanceAtDate(Company $company, $date): float
    {
        $lastTransaction = TransactionEntry::where('company_id', $company->id)
            ->where('created_at', '<=', $date)
            ->where('is_canceled', false)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return $lastTransaction ? (float) $lastTransaction->balance_snapshot : 0.0;
    }

    /**
     * Подтверждение акта компанией
     */
    public function confirmByCompany(): void
    {
        $this->update([
            'confirmed_by_company' => true,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Подтверждение акта платформой
     */
    public function confirmByPlatform(): void
    {
        $this->update([
            'confirmed_by_platform' => true,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Проверка, полностью ли подтвержден акт
     */
    public function isFullyConfirmed(): bool
    {
        return $this->confirmed_by_company && $this->confirmed_by_platform;
    }

    /**
     * Генерация PDF-файла акта сверки
     */
    public function generatePdf(): string
    {
        // Здесь будет логика генерации PDF
        // Можно использовать библиотеку вроде dompdf или laravel-dompdf

        $pdfPath = 'reconciliation_acts/act_'.$this->id.'_'.now()->format('Ymd_His').'.pdf';

        // Сохраняем путь к файлу
        $this->update(['file_path' => $pdfPath]);

        return $pdfPath;
    }

    /**
     * Scope для поиска неподтвержденных актов
     */
    public function scopePending($query)
    {
        return $query->where('confirmed_by_company', false)
            ->orWhere('confirmed_by_platform', false);
    }

    /**
     * Scope для поиска подтвержденных актов
     */
    public function scopeConfirmed($query)
    {
        return $query->where('confirmed_by_company', true)
            ->where('confirmed_by_platform', true);
    }

    /**
     * Scope для поиска актов за период
     */
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
            ->where('period_end', '<=', $endDate);
    }
}
