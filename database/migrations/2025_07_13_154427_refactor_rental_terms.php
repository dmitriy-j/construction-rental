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
        Schema::table('equipment_rental_terms', function (Blueprint $table) {
            $table->dropColumn('period');
            $table->dropColumn('price');
            $table->dropColumn('delivery_price');

            $table->decimal('price_per_hour', 10, 2)->after('equipment_id');
            $table->decimal('price_per_km', 10, 2)->nullable()->after('price_per_hour');
            $table->integer('min_rental_hours')->default(1)->after('price_per_km');
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
