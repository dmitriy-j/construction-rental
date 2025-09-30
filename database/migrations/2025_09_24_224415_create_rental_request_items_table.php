<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalRequestItemsTable extends Migration
{
    public function up()
    {
        Schema::create('rental_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('equipment_categories');
            $table->integer('quantity')->default(1);
            $table->json('specifications')->nullable(); // Характеристики: {"bucket_volume": 2, "hours_worked_max": 5000}
            $table->timestamps();

            $table->index(['rental_request_id', 'category_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('rental_request_items');
    }
}
