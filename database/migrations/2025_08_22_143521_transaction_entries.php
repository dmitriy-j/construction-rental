<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_entries', function (Blueprint $table) {
            $table->id();
            // Связь с компанией, чей баланс изменяется
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            // Сумма проводки. Всегда положительная. Тип соответствует monetary values в существующих таблицах.
            $table->decimal('amount', 12, 2);
            // Тип проводки: дебет (увеличение актива) или кредит (уменьшение актива)
            $table->enum('type', ['debit', 'credit']);
            // Назначение платежа/операции. Определяет природу операции.
            $table->enum('purpose', [
                'lessee_payment',    // Оплата от арендатора
                'lessor_payout',     // Выплата арендодателю
                'platform_fee',      // Зачисление платформенного сбора
                'refund',            // Возврат средств
                'correction',        // Корректировочная проводка
                'upd_debt',           // Фиксация долга по УПД
            ]);
            // Snapshot баланса компании ПОСЛЕ выполнения этой проводки.
            $table->decimal('balance_snapshot', 12, 2);
            // Описание проводки для админки и логов.
            $table->text('description')->nullable();

            // Полиморфная связь для источника операции (проводка создана из заказа, счета, УПД и т.д.)
            $table->nullableMorphs('source');
            // Уникальный ключ идемпотентности для предотвращения дублей (например, хэш от ключевых параметров)
            $table->string('idempotency_key')->nullable()->unique();

            // Статус проводки (например, для отмены)
            $table->boolean('is_canceled')->default(false);
            $table->text('cancel_reason')->nullable();
            $table->timestamp('canceled_at')->nullable();

            $table->timestamps();

            // Индексы для частых запросов
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'purpose']);
            $table->index(['company_id', 'created_at']);
            $table->index('idempotency_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_entries');
    }
};
