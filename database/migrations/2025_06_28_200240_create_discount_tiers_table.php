<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->decimal('min_turnover', 12, 2)->comment('Минимальный оборот для скидки');
            $table->decimal('discount_percent', 5, 2)->comment('Процент скидки');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_tiers');
    }
};
