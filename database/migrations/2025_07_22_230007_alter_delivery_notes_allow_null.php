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
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->string('document_number')->nullable()->change();
            $table->date('issue_date')->nullable()->change();
            $table->date('delivery_date')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->string('document_number')->nullable(false)->change();
            $table->date('issue_date')->nullable(false)->change();
            $table->date('delivery_date')->nullable(false)->change();
        });
    }
};
