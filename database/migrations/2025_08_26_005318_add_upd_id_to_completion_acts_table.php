<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUpdIdToCompletionActsTable extends Migration
{
    public function up()
    {
        Schema::table('completion_acts', function (Blueprint $table) {
            $table->foreignId('upd_id')
                ->nullable()
                ->constrained('upds')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('completion_acts', function (Blueprint $table) {
            $table->dropForeign(['upd_id']);
            $table->dropColumn('upd_id');
        });
    }
}
