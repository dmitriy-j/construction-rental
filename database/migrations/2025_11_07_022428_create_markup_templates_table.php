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
        Schema::create('markup_templates', function (Blueprint $table) {
            $table->id();

            // Основная информация
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('category')->default('general'); // general, seasonal, promotional, etc.

            // Параметры наценки
            $table->string('markupable_type')->nullable();
            $table->string('entity_type')->default('order');
            $table->string('type')->default('fixed');
            $table->string('calculation_type')->default('addition');
            $table->decimal('value', 10, 2)->default(0);
            $table->integer('priority')->default(0);
            $table->json('rules')->nullable();

            // Настройки применения
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // Системные шаблоны нельзя удалять
            $table->integer('usage_count')->default(0);

            // Мета-данные
            $table->json('tags')->nullable();
            $table->string('version')->default('1.0');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->index(['category', 'is_active']);
            $table->index(['name', 'type']);
            $table->index('usage_count');
        });

        // Таблица для истории использования шаблонов
        Schema::create('markup_template_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('markup_templates')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('resulting_markup_id')->nullable()->constrained('platform_markups')->onDelete('set null');

            $table->json('applied_data')->nullable(); // Данные, примененные при создании
            $table->json('customizations')->nullable(); // Изменения, внесенные пользователем

            $table->timestamps();

            // Индексы
            $table->index(['template_id', 'user_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('markup_template_usage');
        Schema::dropIfExists('markup_templates');
    }
};
