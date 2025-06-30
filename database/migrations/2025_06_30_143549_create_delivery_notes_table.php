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
            $table->date('delivery_date')->comment('Дата доставки');
            $table->string('driver_name');
            $table->string('receiver_name');
            $table->string('receiver_signature_path')->nullable()->comment('Путь к подписи');
            $table->text('equipment_condition')->comment('Состояние оборудования');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_notes');
    }
}
