<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDeliveryNotesTable extends Migration
{
    public function up()
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->text('equipment_condition')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->text('equipment_condition')->nullable(false)->change();
        });
    }
}
