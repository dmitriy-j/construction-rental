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
        Schema::create('markup_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_markup_id')->constrained()->onDelete('cascade');

            // Основные метрики
            $table->integer('application_count')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('average_revenue_per_application', 10, 2)->default(0);

            // Метрики эффективности
            $table->decimal('success_rate', 5, 2)->default(0); // Процент успешных применений
            $table->decimal('average_response_time', 8, 2)->default(0); // Среднее время ответа в мс

            // Временные метрики
            $table->date('last_application_date')->nullable();
            $table->date('first_application_date')->nullable();

            // Детализация по типам
            $table->json('applications_by_entity_type')->nullable(); // {order: 10, rental_request: 5, ...}
            $table->json('revenue_by_period')->nullable(); // {daily: [], weekly: [], monthly: []}

            $table->timestamps();

            // Индексы для быстрого поиска
            $table->index(['platform_markup_id', 'last_application_date']);
            $table->index(['application_count', 'total_revenue']);
        });

        // Таблица для ежедневной статистики
        Schema::create('markup_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date');
            $table->foreignId('platform_markup_id')->constrained()->onDelete('cascade');

            // Ежедневные метрики
            $table->integer('applications_count')->default(0);
            $table->decimal('daily_revenue', 15, 2)->default(0);
            $table->integer('calculation_errors')->default(0);
            $table->decimal('average_calculation_time', 8, 2)->default(0);

            // Пиковые значения
            $table->integer('peak_applications_hour')->nullable();
            $table->decimal('peak_revenue_hour', 15, 2)->nullable();

            $table->timestamps();

            // Уникальный индекс для предотвращения дублирования
            $table->unique(['stat_date', 'platform_markup_id']);
            $table->index(['stat_date', 'applications_count']);
        });

        // Таблица для агрегированной статистики
        Schema::create('markup_aggregated_stats', function (Blueprint $table) {
            $table->id();
            $table->string('period_type'); // daily, weekly, monthly, yearly
            $table->date('period_start');
            $table->date('period_end');

            // Агрегированные метрики
            $table->integer('total_applications')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->integer('active_markups_count')->default(0);
            $table->integer('new_markups_count')->default(0);
            $table->integer('expired_markups_count')->default(0);

            // Метрики по типам
            $table->json('applications_by_type')->nullable(); // {fixed: 100, percent: 50, ...}
            $table->json('revenue_by_type')->nullable();
            $table->json('applications_by_entity')->nullable(); // {order: 120, rental_request: 30, ...}

            // Метрики производительности
            $table->decimal('average_response_time', 8, 2)->default(0);
            $table->decimal('cache_hit_rate', 5, 2)->default(0);
            $table->integer('total_errors')->default(0);

            $table->timestamps();

            // Индексы
            $table->unique(['period_type', 'period_start']);
            $table->index(['period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('markup_aggregated_stats');
        Schema::dropIfExists('markup_daily_stats');
        Schema::dropIfExists('markup_statistics');
    }
};
