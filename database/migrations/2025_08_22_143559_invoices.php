<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            // Ссылка на заказ. Используется parent_order_id из агрегированного заказа арендатора.
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade'); // Компания-плательщик

            // Реквизиты счета
            $table->string('number')->unique(); // Формат: СЧ-20250101-0001
            $table->date('issue_date');
            $table->date('due_date'); // Срок оплаты

            // Суммы к оплате
            $table->decimal('amount', 12, 2); // Сумма счета
            $table->decimal('amount_paid', 12, 2)->default(0); // Оплаченная сумма
            $table->decimal('platform_fee', 12, 2)->default(0); // Комиссия платформы

            // Статус счета
            $table->enum('status', ['draft', 'sent', 'viewed', 'paid', 'overdue', 'canceled'])->default('draft');

            // Ссылка на файл PDF
            $table->string('file_path')->nullable();

            // Idempotency
            $table->string('idempotency_key')->nullable()->unique();

            $table->timestamps();
            $table->timestamp('paid_at')->nullable();

            // Индексы
            $table->index(['company_id', 'status']);
            $table->index('number');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
