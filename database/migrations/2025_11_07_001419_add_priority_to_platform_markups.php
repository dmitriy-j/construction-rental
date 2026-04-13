<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_markups', function (Blueprint $table) {
            $table->integer('priority')->default(0)->after('valid_to'); // Добавляем столбец priority
            $table->index(['priority', 'is_active']); // Индекс для priority и is_active
            $table->index(['entity_type', 'priority']); // Индекс для entity_type и priority
        });
    }

    public function down(): void
    {
        Schema::table('platform_markups', function (Blueprint $table) {
            // Удаляем столбец priority только если он существует
            if (Schema::hasColumn('platform_markups', 'priority')) {
                $table->dropColumn('priority'); // Удаляем столбец priority
            }
        });
    }
};
