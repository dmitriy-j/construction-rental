<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upds', function (Blueprint $table) {
            $table->id();

            // Связи с основными сущностями
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('lessor_company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('lessee_company_id')->constrained('companies')->onDelete('cascade');

            // Реквизиты документа (обязательные для 1С)
            $table->string('number'); // Номер УПД
            $table->date('issue_date'); // Дата составления
            $table->date('service_period_start'); // Начало периода оказания услуг
            $table->date('service_period_end'); // Конец периода оказания услуг

            // Финансовые данные
            $table->decimal('amount', 15, 2); // Сумма без НДС
            $table->decimal('tax_amount', 15, 2)->default(0); // Сумма НДС
            $table->decimal('total_amount', 15, 2); // Итого с НДС
            $table->string('tax_system'); // Система налогообложения

            // Реквизиты договора
            $table->string('contract_number')->nullable(); // Номер договора
            $table->date('contract_date')->nullable(); // Дата договора

            // Реквизиты счета
            $table->string('invoice_number')->nullable(); // Номер счета
            $table->date('invoice_date')->nullable(); // Дата счета

            // Статус и workflow
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->string('file_path')->nullable(); // Путь к файлу УПД
            $table->string('idempotency_key')->nullable(); // Ключ идемпотентности

            // Даты обработки
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Индексы для оптимизации запросов
            $table->index('number');
            $table->index('issue_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upds');
    }
};
