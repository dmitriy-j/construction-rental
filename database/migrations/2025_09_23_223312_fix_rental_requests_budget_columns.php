<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            // Возвращаем decimal для совместимости с brick/math
            $table->decimal('budget_from', 12, 2)->change();
            $table->decimal('budget_to', 12, 2)->change();
        });
    }

    public function down()
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            $table->float('budget_from', 12, 2)->change();
            $table->float('budget_to', 12, 2)->change();
        });
    }
};
