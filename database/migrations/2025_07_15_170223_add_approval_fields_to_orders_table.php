<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Изменяем перечень статусов
            $table->enum('status', [
                'pending',          // Ожидает подтверждения
                'pending_approval', // Ожидает подтверждения арендодателем
                'confirmed',        // Подтвержден арендодателем
                'active',           // Исполняется
                'completed',        // Завершен
                'cancelled',        // Отменен арендатором
                'rejected',          // Отклонен арендодателем
            ])->default('pending')->change();

            // Добавляем новые поля
            $table->text('rejection_reason')->nullable()->after('status');
            $table->timestamp('confirmed_at')->nullable()->after('rejection_reason');
            $table->timestamp('rejected_at')->nullable()->after('confirmed_at');
            $table->foreignId('rental_condition_id')
                ->nullable()
                ->constrained('rental_conditions')
                ->onDelete('set null')
                ->after('lessor_company_id');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Возвращаем оригинальные статусы
            $table->enum('status', [
                'pending',
                'confirmed',
                'active',
                'completed',
                'cancelled',
            ])->default('pending')->change();

            $table->dropColumn([
                'rejection_reason',
                'confirmed_at',
                'rejected_at',
                'rental_condition_id',
            ]);
        });
    }
};
