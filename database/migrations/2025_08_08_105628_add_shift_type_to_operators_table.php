<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShiftTypeToOperatorsTable extends Migration
{
    public function up()
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->enum('shift_type', ['day', 'night'])->default('day')->after('is_active');
        });
    }

    public function down()
    {
        Schema::table('operators', function (Blueprint $table) {
            $table->dropColumn('shift_type');
        });
    }
}
