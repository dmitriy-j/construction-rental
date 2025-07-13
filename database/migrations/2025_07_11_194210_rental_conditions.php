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
        Schema::create('rental_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->nullable()->constrained();
            $table->foreignId('company_id')->nullable()->constrained();
            $table->integer('shift_hours')->default(8);
            $table->integer('shifts_per_day')->default(1);
            $table->enum('transportation', ['lessor', 'lessee', 'shared']);
            $table->enum('fuel_responsibility', ['lessor', 'lessee', 'shared']);
            $table->enum('extension_policy', ['allowed', 'not_allowed', 'conditional']);
            $table->enum('payment_type', ['hourly', 'shift', 'daily', 'mileage', 'volume']);
            $table->decimal('fuel_consumption_rate', 8, 2)->nullable();
            $table->decimal('distance_rate', 8, 2)->nullable();
            $table->decimal('volume_rate', 8, 2)->nullable();
            $table->boolean('is_default')->default(false);
            $table->foreignId('delivery_location_id')->nullable()->constrained('locations');
            $table->decimal('delivery_cost_per_km', 8, 2)->default(50);
            $table->decimal('loading_cost', 8, 2)->default(1000);
            $table->decimal('unloading_cost', 8, 2)->default(1000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
