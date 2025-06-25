<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['lessor', 'lessee']); // Арендодатель/Арендатор
            $table->string('legal_name');
            $table->enum('tax_system', ['vat', 'no_vat']);
            $table->string('inn', 12);
            $table->string('kpp', 9);
            $table->string('ogrn', 13);
            $table->string('okpo', 10)->nullable();
            $table->text('legal_address');
            $table->text('actual_address')->nullable();
            $table->string('bank_name');
            $table->string('bank_account', 20);
            $table->string('bik', 9);
            $table->string('correspondent_account', 20)->nullable();
            $table->string('director_name');
            $table->string('phone');
            $table->text('contacts')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();



            // Добавляем статусы верификации
            $table->enum('status', [
                'pending',         // Ожидает проверки
                'verified',        // Проверено и подтверждено
                'rejected'         // Отклонено
            ])->default('pending');

            $table->text('rejection_reason')->nullable(); // Причина отклонения
            $table->timestamp('verified_at')->nullable();  // Дата верификации
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
