<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDatesToContractsTable extends Migration
{
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->date('start_date')->after('company_id');
            $table->date('end_date')->after('start_date');


            // Удалим ненужные поля, если они есть в ошибке
        });
    }

    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {



        });
    }
}
