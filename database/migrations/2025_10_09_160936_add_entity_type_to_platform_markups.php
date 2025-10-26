<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEntityTypeToPlatformMarkups extends Migration
{
    public function up()
    {
        Schema::table('platform_markups', function (Blueprint $table) {
            if (!Schema::hasColumn('platform_markups', 'entity_type')) {
                $table->string('entity_type')->default('order')->after('type');
            }
            if (!Schema::hasColumn('platform_markups', 'calculation_type')) {
                $table->string('calculation_type')->default('addition')->after('entity_type');
            }
        });
    }

    public function down()
    {
        Schema::table('platform_markups', function (Blueprint $table) {
            $table->dropColumn(['entity_type', 'calculation_type']);
        });
    }
}
