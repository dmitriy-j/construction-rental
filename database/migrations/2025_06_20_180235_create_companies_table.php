<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('name');
            $table->boolean('vat')->default(false);
            $table->string('inn', 12);
            $table->string('kpp', 9)->nullable();
            $table->string('ogrn', 15);
            $table->string('okpo', 10)->nullable();
            $table->string('legal_address');
            $table->string('actual_address')->nullable();
            $table->boolean('same_address')->default(false);
            $table->string('bank_name');
            $table->string('bank_account', 20);
            $table->string('bik', 9);
            $table->string('correspondent_account', 20);
            $table->string('director');
            $table->string('phone');
            $table->string('manager')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
