<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('legal_name');
            $table->boolean('has_vat')->default(false);
            $table->string('inn');
            $table->string('ogrn');
            $table->string('legal_address');
            $table->boolean('is_actual_same')->default(false);
            $table->string('bank_name');
            $table->string('checking_account');
            $table->string('bik');
            $table->string('correspondent_account');

             // Необязательные поля
            $table->string('kpp')->nullable();
            $table->string('okpo')->nullable();
            $table->string('actual_address')->nullable();
            $table->string('director')->nullable();
            $table->string('phone')->nullable();
            $table->string('manager')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'password',
                'legal_name',
                'has_vat',
                'inn',
                'kpp',
                'ogrn',
                'okpo',
                'legal_address',
                'is_actual_same',
                'actual_address',
                'bank_name',
                'checking_account',
                'bik',
                'correspondent_account',
                'director',
                'phone',
                'manager'
            ]);
        });
    }
};

