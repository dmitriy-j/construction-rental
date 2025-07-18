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
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('shift_hours')->after('rental_condition_id')->nullable();
            $table->integer('shifts_per_day')->after('shift_hours')->nullable();
            $table->string('payment_type', 20)->after('shifts_per_day')->nullable();
            $table->string('transportation', 20)->after('payment_type')->nullable();
            $table->string('fuel_responsibility', 20)->after('transportation')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
