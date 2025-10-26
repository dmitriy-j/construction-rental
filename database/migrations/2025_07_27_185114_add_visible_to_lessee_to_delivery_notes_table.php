<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVisibleToLesseeToDeliveryNotesTable extends Migration
{
    public function up()
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->boolean('visible_to_lessee')
                ->default(false)
                ->comment('Видимость ТН для арендатора');
        });
    }

    public function down()
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropColumn('visible_to_lessee');
        });
    }
}
