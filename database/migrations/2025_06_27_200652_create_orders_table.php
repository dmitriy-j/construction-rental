<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_id')
                ->nullable()
                ->constrained('platforms')
                ->onDelete('set null');

            $table->foreignId('lessee_company_id')->constrained('companies')->comment('Арендатор');
            $table->foreignId('lessor_company_id')->constrained('companies')->comment('Арендодатель');
            $table->foreignId('user_id')->constrained()->comment('Ответственный за заказ');

            $table->enum('status', [
                'pending',      // Ожидает подтверждения
                'confirmed',    // Подтвержден арендодателем
                'active',       // Исполняется
                'completed',    // Завершен
                'cancelled'     // Отменен
            ])->default('pending');

            $table->decimal('total_amount', 12, 2);
            $table->decimal('base_amount', 12, 2)->default(0)->comment('Сумма без наценки');
            $table->decimal('platform_fee', 12, 2)->default(0)->comment('Наценка платформы');
            $table->decimal('discount_amount', 12, 2)->default(0)->comment('Скидка для арендатора');
            $table->decimal('lessor_payout', 12, 2)->default(0)->comment('Сумма к выплате арендодателю');

            // Убрали метод after() - просто добавляем столбец в нужной позиции
            $table->decimal('penalty_amount', 12, 2)->default(0)->comment('Штрафы за простой или повреждение');

            $table->text('notes')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
