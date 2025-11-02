<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rental_request_items', function (Blueprint $table) {
            $table->json('custom_specs_metadata')->nullable()->after('specifications');
        });
    }

    public function down()
    {
        Schema::table('rental_request_items', function (Blueprint $table) {
            $table->dropColumn('custom_specs_metadata');
        });
    }
};
