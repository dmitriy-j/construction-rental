<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('equipment_specifications', function (Blueprint $table) {
            $table->decimal('weight', 10, 2)->nullable()->comment('Вес в кг');
            $table->decimal('length', 10, 2)->nullable()->comment('Длина в метрах');
            $table->decimal('width', 10, 2)->nullable()->comment('Ширина в метрах');
            $table->decimal('height', 10, 2)->nullable()->comment('Высота в метрах');
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
