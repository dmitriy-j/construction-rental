<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUpdIdToWaybillsTable extends Migration
{
    public function up()
    {
        Schema::table('waybills', function (Blueprint $table) {
            $table->foreignId('upd_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('waybills', function (Blueprint $table) {
            $table->dropForeign(['upd_id']);
            $table->dropColumn('upd_id');
        });
    }
}
