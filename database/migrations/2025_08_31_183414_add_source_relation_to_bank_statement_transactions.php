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
        Schema::table('bank_statement_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_statement_transactions', 'source_type')) {
                $table->string('source_type')->nullable()->after('error_message');
            }
            if (! Schema::hasColumn('bank_statement_transactions', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }
        });
    }

    public function down()
    {
        Schema::table('bank_statement_transactions', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
