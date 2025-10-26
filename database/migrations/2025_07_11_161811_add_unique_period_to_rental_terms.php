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
        Schema::table('equipment_rental_terms', function (Blueprint $table) {
            $table->unique(['equipment_id', 'period'], 'equipment_period_unique');
        });
    }

    public function down()
    {
        Schema::table('equipment_rental_terms', function (Blueprint $table) {
            $table->dropUnique('equipment_period_unique');
        });
    }
};
