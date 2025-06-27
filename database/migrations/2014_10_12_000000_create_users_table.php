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
            $table->string('phone')->nullable(); // Добавлено
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            $table->enum('type', [
                'tenant',       // Арендатор
                'landlord',     // Арендодатель
                'admin',        // Администратор платформы
                'staff'         // Сотрудник компании
            ])->default('tenant');

            $table->enum('position', [
                'admin',
                'manager',
                'dispatcher',
                'accountant'
            ])->nullable();

            $table->enum('role', [
                'company_admin',    // Админ компании
                'platform_support', // Поддержка платформы
                'platform_moder',   // Модератор платформы
                'platform_manager', // Менеджер платформы
                'platform_super'    // Суперадмин
            ])->nullable();

            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
