<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Временно отключаем проверку внешних ключей
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Проверяем существование индексов и удаляем их, если они есть
        $indexes = DB::select('SHOW INDEXES FROM orders');
        $indexNames = array_column($indexes, 'Key_name');

        if (in_array('orders_lessee_company_id_company_order_number_unique', $indexNames)) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique('orders_lessee_company_id_company_order_number_unique');
            });
        }

        if (in_array('orders_lessor_company_id_company_order_number_unique', $indexNames)) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique('orders_lessor_company_id_company_order_number_unique');
            });
        }

        // Включаем проверку внешних ключей обратно
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down()
    {
        // Операция down может быть не нужна, если это исправление
        // Но для целостности миграции можно оставить пустой метод
        // или реализовать обратное создание индексов, если потребуется
    }
};
