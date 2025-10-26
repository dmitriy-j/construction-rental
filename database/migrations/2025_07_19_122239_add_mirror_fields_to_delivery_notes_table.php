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
            $table->boolean('is_mirror')->default(false);
            $table->foreignId('original_note_id')->nullable()->constrained('delivery_notes');
        });
    }

    public function down()
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropColumn('is_mirror');
            $table->dropForeign(['original_note_id']);
            $table->dropColumn('original_note_id');
        });
    }
};
