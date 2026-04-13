<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('platform_markups', function (Blueprint $table) {
            // Добавляем поле для расширенных правил (JSON)
            if (!Schema::hasColumn('platform_markups', 'rules')) {
                $table->json('rules')->nullable()->after('calculation_type');
            }

            // Добавляем поле для активации/деактивации
            if (!Schema::hasColumn('platform_markups', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('value');
            }

            // Добавляем временные рамки действия наценки
            if (!Schema::hasColumn('platform_markups', 'valid_from')) {
                $table->timestamp('valid_from')->nullable()->after('is_active');
            }

            if (!Schema::hasColumn('platform_markups', 'valid_to')) {
                $table->timestamp('valid_to')->nullable()->after('valid_from');
            }

            // Добавляем поле для приоритета
            if (!Schema::hasColumn('platform_markups', 'priority')) {
                $table->integer('priority')->default(0)->after('valid_to');
            }

            // Добавляем индексы для оптимизации поиска
            $table->index(['entity_type', 'is_active', 'valid_from', 'valid_to']);
            $table->index(['markupable_type', 'markupable_id', 'entity_type']);
        });

        // Создаем таблицу для аудита изменений наценок
        Schema::create('platform_markup_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_markup_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // created, updated, deleted
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['platform_markup_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::table('platform_markups', function (Blueprint $table) {
            $table->dropColumn(['rules', 'is_active', 'valid_from', 'valid_to', 'priority']);
            $table->dropIndex(['entity_type', 'is_active', 'valid_from', 'valid_to']);
            $table->dropIndex(['markupable_type', 'markupable_id', 'entity_type']);
        });

        Schema::dropIfExists('platform_markup_audits');
    }
};
