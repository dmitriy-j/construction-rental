<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->foreignId('equipment_import_id')
                  ->nullable()
                  ->constrained('equipment_imports')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropForeign(['equipment_import_id']);
            $table->dropColumn('equipment_import_id');
        });
    }
};
