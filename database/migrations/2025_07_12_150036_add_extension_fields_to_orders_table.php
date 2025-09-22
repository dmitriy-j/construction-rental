<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('extension_requested')->default(false)->after('contract_date');
            $table->dateTime('requested_end_date')->nullable()->after('extension_requested');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['extension_requested', 'requested_end_date']);
        });
    }
};
