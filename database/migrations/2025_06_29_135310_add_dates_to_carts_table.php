<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dateTime('start_date')->nullable()->after('discount_amount');
            $table->dateTime('end_date')->nullable()->after('start_date');

            // Добавьте индексы для частых запросов
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
