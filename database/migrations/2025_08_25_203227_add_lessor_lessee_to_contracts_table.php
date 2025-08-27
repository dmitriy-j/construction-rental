<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('lessor_company_id')->nullable()->constrained('companies');
            $table->foreignId('lessee_company_id')->nullable()->constrained('companies');

            // Добавляем индексы для производительности
            $table->index('lessor_company_id');
            $table->index('lessee_company_id');
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['lessor_company_id']);
            $table->dropForeign(['lessee_company_id']);
            $table->dropColumn(['lessor_company_id', 'lessee_company_id']);
        });
    }
};
