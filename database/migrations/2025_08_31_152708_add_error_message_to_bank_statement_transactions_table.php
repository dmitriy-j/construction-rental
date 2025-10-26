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
        if (! Schema::hasColumn('bank_statement_transactions', 'error_message')) {
            Schema::table('bank_statement_transactions', function (Blueprint $table) {
                $table->text('error_message')->nullable()->after('status');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('bank_statement_transactions', 'error_message')) {
            Schema::table('bank_statement_transactions', function (Blueprint $table) {
                $table->dropColumn('error_message');
            });
        }
    }
};
