<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderIdToCompletionActsTable extends Migration
{
    public function up()
    {
        Schema::table('completion_acts', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->after('id'); // Добавление столбца order_id
        });
    }

    public function down()
    {
        Schema::table('completion_acts', function (Blueprint $table) {
            $table->dropColumn('order_id'); // Удаление столбца order_id
        });
    }
}
