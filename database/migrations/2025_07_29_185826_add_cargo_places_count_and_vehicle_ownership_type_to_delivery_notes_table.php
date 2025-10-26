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
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->integer('cargo_places_count')->default(1)->after('cargo_value');
            $table->string('special_permission')->nullable()->after('transport_vehicle_number');
            $table->string('ownership_type')->default('1')->after('special_permission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            //
        });
    }
};
