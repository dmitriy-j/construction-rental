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
        Schema::table('waybill_shifts', function (Blueprint $table) {
            $table->time('departure_time')->nullable()->change();
            $table->time('return_time')->nullable()->change();
            $table->integer('odometer_start')->nullable()->change();
            $table->integer('odometer_end')->nullable()->change();
            $table->decimal('fuel_start', 10, 2)->nullable()->change();
            $table->decimal('fuel_end', 10, 2)->nullable()->change();
            $table->decimal('fuel_refilled_liters', 10, 2)->nullable()->change();
            $table->string('fuel_refilled_type', 50)->nullable()->change();
            $table->decimal('hours_worked', 5, 2)->nullable()->change();
            $table->decimal('downtime_hours', 5, 2)->nullable()->change();
            $table->string('downtime_cause', 500)->nullable()->change();
            $table->text('work_description')->nullable()->change();
            $table->decimal('total_amount', 10, 2)->nullable()->change();
            $table->string('operator_signature_path')->nullable()->change();
            $table->string('mechanic_signature_path')->nullable()->change();
            $table->string('dispatcher_signature_path')->nullable()->change();
            $table->string('foreman_signature_path')->nullable()->change();
        });
    }

    public function down()
    {
        // Обратное преобразование не рекомендуется для production
        // Но для полноты решения:
        Schema::table('waybill_shifts', function (Blueprint $table) {
            $table->time('departure_time')->nullable(false)->change();
            $table->time('return_time')->nullable()->change();
            $table->integer('odometer_start')->nullable()->change();
            $table->integer('odometer_end')->nullable()->change();
            $table->decimal('fuel_start', 10, 2)->nullable()->change();
            $table->decimal('fuel_end', 10, 2)->nullable()->change();
            $table->decimal('fuel_refilled_liters', 10, 2)->nullable()->change();
            $table->string('fuel_refilled_type', 50)->nullable()->change();
            $table->decimal('hours_worked', 5, 2)->nullable()->change();
            $table->decimal('downtime_hours', 5, 2)->nullable()->change();
            $table->string('downtime_cause', 500)->nullable()->change();
            $table->text('work_description')->nullable()->change();
            $table->decimal('total_amount', 10, 2)->nullable()->change();
            $table->string('operator_signature_path')->nullable()->change();
            $table->string('mechanic_signature_path')->nullable()->change();
            $table->string('dispatcher_signature_path')->nullable()->change();
            $table->string('foreman_signature_path')->nullable()->change();
        });
    }
};
