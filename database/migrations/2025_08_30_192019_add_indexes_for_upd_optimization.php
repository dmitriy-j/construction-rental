<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesForUpdOptimization extends Migration
{
    /**
     * Run the migrations.
     */
    // В методе up() вашего файла миграции
    public function up()
    {
        if (! Schema::hasColumn('upds', 'waybill_id')) {
            Schema::table('upds', function (Blueprint $table) {
                $table->unsignedBigInteger('waybill_id')->nullable()->after('lessee_company_id');
                // Добавьте при необходимости внешние ключи или индексы
                // $table->foreign('waybill_id')->references('id')->on('waybills');
            });
        }
    }

    // В методе down()
    public function down()
    {
        if (Schema::hasColumn('upds', 'waybill_id')) {
            Schema::table('upds', function (Blueprint $table) {
                // Сначала удалите внешний ключ, если он был создан
                // $table->dropForeign(['waybill_id']);
                $table->dropColumn('waybill_id');
            });
        }
    }
}
