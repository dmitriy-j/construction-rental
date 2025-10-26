<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('equipment_availability', function (Blueprint $table) {
            $table->uuid('reservation_token')->nullable()->after('order_id');
            $table->index(['reservation_token']);
            $table->index(['equipment_id', 'date', 'status']);
        });
    }

    public function down()
    {
        Schema::table('equipment_availability', function (Blueprint $table) {
            $table->dropIndex(['reservation_token']);
            $table->dropIndex(['equipment_id', 'date', 'status']);
            $table->dropColumn('reservation_token');
        });
    }
};
