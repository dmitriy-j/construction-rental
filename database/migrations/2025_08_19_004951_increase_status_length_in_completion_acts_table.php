<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('completion_acts', function (Blueprint $table) {
            $table->string('status', 20)->change();
        });
    }

    public function down()
    {
        Schema::table('completion_acts', function (Blueprint $table) {
            $table->string('status', 10)->change(); // Предположим, что было 10, а нужно 20. Или было меньше?
        });
    }
};
