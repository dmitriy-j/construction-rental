<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAbTestingToProposalTemplates extends Migration
{
    public function up()
{
    // Добавляем поля в основную таблицу proposal_templates
    Schema::table('proposal_templates', function (Blueprint $table) {
        if (!Schema::hasColumn('proposal_templates', 'is_ab_test')) {
            $table->boolean('is_ab_test')->default(false);
        }

        if (!Schema::hasColumn('proposal_templates', 'ab_test_variants')) {
            $table->json('ab_test_variants')->nullable();
        }

        if (!Schema::hasColumn('proposal_templates', 'test_distribution')) {
            $table->string('test_distribution')->default('50-50');
        }

        if (!Schema::hasColumn('proposal_templates', 'test_metric')) {
            $table->string('test_metric')->default('conversion');
        }

        if (!Schema::hasColumn('proposal_templates', 'ab_test_started_at')) {
            $table->timestamp('ab_test_started_at')->nullable();
        }

        if (!Schema::hasColumn('proposal_templates', 'ab_test_winner')) {
            $table->integer('ab_test_winner')->nullable();
        }

        if (!Schema::hasColumn('proposal_templates', 'ab_test_status')) {
            $table->string('ab_test_status')->default('active');
        }
    });

    // Создаем таблицу для статистики A/B тестов
    Schema::create('proposal_template_ab_test_stats', function (Blueprint $table) {
        $table->id();
        $table->foreignId('proposal_template_id')->constrained()->onDelete('cascade');
        $table->integer('variant_index');
        $table->string('variant_name');
        $table->integer('impressions')->default(0);
        $table->integer('applications')->default(0);
        $table->integer('conversions')->default(0);
        $table->decimal('total_revenue', 10, 2)->default(0);
        $table->timestamps();

        // Задаем более короткое имя для уникального индекса
        $table->unique(['proposal_template_id', 'variant_index'], 'unique_proposal_variant');
        $table->index(['proposal_template_id']); // Для быстрого поиска по шаблону
    });
}

    public function down()
    {
        // Удаляем таблицу статистики
        Schema::dropIfExists('proposal_template_ab_test_stats');

        // Удаляем поля из основной таблицы
        Schema::table('proposal_templates', function (Blueprint $table) {
            $table->dropColumn([
                'is_ab_test',
                'ab_test_variants',
                'test_distribution',
                'test_metric',
                'ab_test_started_at',
                'ab_test_winner',
                'ab_test_status'
            ]);
        });
    }
}
