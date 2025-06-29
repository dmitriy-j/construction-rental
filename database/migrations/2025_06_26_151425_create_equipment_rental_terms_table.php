<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
    {
        Schema::create('equipment_rental_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment');
            $table->string('period'); // час, смена, сутки, месяц
            $table->decimal('price', 10, 2);
            $table->decimal('delivery_price', 10, 2)->default(0);
            $table->integer('delivery_days')->default(1); // Срок доставки в днях
            $table->text('return_policy')->nullable();
            $table->string('currency', 3)->default('RUB');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_rental_terms');
    }
};
