<?php
// database/migrations/2025_02_15_add_priority_to_platform_markups.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_markups', function (Blueprint $table) {
            // Добавляем столбец priority, если его нет
            if (!Schema::hasColumn('platform_markups', 'priority')) {
                $table->integer('priority')->default(0)->after('valid_to');
            }

            // Добавляем индексы для оптимизации только если они не существуют
            if (!Schema::hasIndex('platform_markups', ['priority', 'is_active'])) {
                $table->index(['priority', 'is_active']);
            }
            if (!Schema::hasIndex('platform_markups', ['entity_type', 'priority'])) {
                $table->index(['entity_type', 'priority']);
            }
            if (!Schema::hasIndex('platform_markups', ['markupable_type', 'markupable_id', 'priority'])) {
                $table->index(['markupable_type', 'markupable_id', 'priority']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('platform_markups', function (Blueprint $table) {
            // Удаляем индексы
            $table->dropIndex(['priority', 'is_active']);
            $table->dropIndex(['entity_type', 'priority']);
            $table->dropIndex(['markupable_type', 'markupable_id', 'priority']);

            // Удаляем столбец priority
            if (Schema::hasColumn('platform_markups', 'priority')) {
                $table->dropColumn('priority');
            }
        });
    }
};
