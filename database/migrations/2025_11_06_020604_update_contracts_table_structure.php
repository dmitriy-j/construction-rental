<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateContractsTableStructure extends Migration
{
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Удаляем внешние ключи перед удалением столбцов
            if (Schema::hasColumn('contracts', 'lessor_company_id')) {
                $table->dropForeign(['lessor_company_id']);
            }
            if (Schema::hasColumn('contracts', 'lessee_company_id')) {
                $table->dropForeign(['lessee_company_id']);
            }

            // Удаляем старые колонки
            if (Schema::hasColumn('contracts', 'lessor_company_id') || Schema::hasColumn('contracts', 'lessee_company_id')) {
                $table->dropColumn(['lessor_company_id', 'lessee_company_id']);
            }

            // Добавляем новую колонку для типа контрагента, если ее еще нет
            if (!Schema::hasColumn('contracts', 'counterparty_type')) {
                $table->enum('counterparty_type', ['lessor', 'lessee'])->after('company_id');
            }

            // Добавляем колонку для ID контрагента, если ее еще нет
            if (!Schema::hasColumn('contracts', 'counterparty_company_id')) {
                $table->unsignedBigInteger('counterparty_company_id')->after('counterparty_type');
            }

            // Добавляем внешний ключ
            $table->foreign('counterparty_company_id')->references('id')->on('companies');

            // Добавляем индекс для оптимизации запросов
            $table->index(['counterparty_type', 'counterparty_company_id']);
        });
    }

    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            // Восстанавливаем старую структуру при откате
            if (Schema::hasColumn('contracts', 'counterparty_company_id')) {
                $table->dropForeign(['counterparty_company_id']);
                $table->dropIndex(['counterparty_type', 'counterparty_company_id']);
                $table->dropColumn(['counterparty_type', 'counterparty_company_id']);
            }

            // Восстанавливаем старые колонки
            if (!Schema::hasColumn('contracts', 'lessor_company_id')) {
                $table->unsignedBigInteger('lessor_company_id')->nullable();
            }
            if (!Schema::hasColumn('contracts', 'lessee_company_id')) {
                $table->unsignedBigInteger('lessee_company_id')->nullable();
            }

            // Добавляем внешние ключи для старых колонок
            $table->foreign('lessor_company_id')->references('id')->on('companies');
            $table->foreign('lessee_company_id')->references('id')->on('companies');
        });
    }
}
