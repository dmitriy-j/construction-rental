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

        // Удаляем внешние ключи
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['lessee_company_id']); // Замените на фактическое имя колонки
            $table->dropForeign(['lessor_company_id']); // Замените на фактическое имя колонки
        });

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
        // Реализуйте обратные операции, если это необходимо
    }
};
