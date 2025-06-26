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
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('category_id')->constrained('equipment_categories');
            $table->foreignId('location_id')->constrained('locations');
            $table->string('brand');
            $table->string('model');
            $table->integer('year');
            $table->float('hours_worked', 8, 2);
            $table->float('rating', 2, 1)->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
