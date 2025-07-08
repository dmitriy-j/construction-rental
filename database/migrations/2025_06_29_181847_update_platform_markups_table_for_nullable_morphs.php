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
        Schema::table('platform_markups', function (Blueprint $table) {
            // Делаем поля nullable
            $table->string('markupable_type')->nullable()->change();
            $table->unsignedBigInteger('markupable_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('platform_markups', function (Blueprint $table) {
            $table->string('markupable_type')->nullable(false)->change();
            $table->unsignedBigInteger('markupable_id')->nullable(false)->change();
        });
    }
};
