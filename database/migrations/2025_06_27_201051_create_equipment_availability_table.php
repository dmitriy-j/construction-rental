<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('equipment_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained();
            $table->date('date');
            $table->enum('status', ['available', 'booked', 'maintenance'])->default('available');
            $table->foreignId('order_id')->nullable()->constrained();
            $table->timestamps();

            $table->index(['equipment_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_availability');
    }
};
