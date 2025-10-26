<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWaybillShiftsTable extends Migration
{
    public function up()
    {
        Schema::create('waybill_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waybill_id')->constrained()->onDelete('cascade');
            $table->date('shift_date');
            $table->enum('shift_type', ['day', 'night']);
            $table->foreignId('operator_id')->constrained('operators');

            // Адрес и время
            $table->string('object_address');
            $table->time('departure_time');
            $table->time('return_time');

            // Показатели техники
            $table->integer('odometer_start')->default(0);
            $table->integer('odometer_end')->default(0);
            $table->decimal('fuel_start', 10, 2)->default(0.00);
            $table->decimal('fuel_end', 10, 2)->default(0.00);
            $table->decimal('fuel_refilled_liters', 10, 2)->default(0.00);
            $table->string('fuel_refilled_type', 50)->nullable();

            // Рабочие показатели
            $table->decimal('hours_worked', 5, 2)->default(0.00);
            $table->decimal('downtime_hours', 5, 2)->default(0.00);
            $table->text('downtime_cause')->nullable();
            $table->text('work_description')->nullable();

            // Финансы
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('total_amount', 12, 2);

            // Подписи
            $table->string('operator_signature_path')->nullable();
            $table->string('mechanic_signature_path')->nullable();
            $table->string('dispatcher_signature_path')->nullable();
            $table->string('foreman_signature_path')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waybill_shifts');
    }
}
