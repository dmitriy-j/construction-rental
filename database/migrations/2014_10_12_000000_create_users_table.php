<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Изменяем тип ролей
            $table->enum('type', [
                'tenant',       // Арендатор
                'landlord',     // Арендодатель
                'admin'         // Администратор платформы
            ])->default('tenant');

            // Детализация ролей для админов
            $table->enum('role', [
                'company_admin',    // Админ компании (юрлица)
                'platform_support', // Поддержка платформы
                'platform_moder',   // Модератор платформы
                'platform_manager', // Менеджер платформы
                'platform_super'    // Суперадмин
            ])->nullable();

            // Связь с компанией (для всех кроме суперадминов)
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');

            // Для 2FA админов
            $table->string('two_factor_secret')->nullable();
            $table->string('two_factor_recovery_codes')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
