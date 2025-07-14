<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryNotesTable extends Migration
{
    public function up()
    {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('delivery_from_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->foreignId('delivery_to_id')->nullable()->constrained('locations')->onDelete('set null');
            $table->date('delivery_date');
            $table->string('driver_name');
            $table->string('receiver_name');
            $table->string('receiver_signature_path')->nullable();
            $table->text('equipment_condition')->nullable();

            // Поля для расчета стоимости
            $table->string('vehicle_type')->default('truck_25t'); // 25t, 45t, 110t
            $table->unsignedInteger('distance_km'); // расстояние в км
            $table->decimal('calculated_cost', 10, 2); // расчетная стоимость

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_notes');
    }
}
