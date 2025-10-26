<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('waybills', function (Blueprint $table) {
            $table->enum('perspective', ['lessor', 'lessee'])->default('lessor');
            $table->foreignId('related_waybill_id')->nullable()->constrained('waybills')->onDelete('set null');
            $table->foreignId('parent_order_id')->nullable()->constrained('orders')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('waybills', function (Blueprint $table) {
            $table->dropForeign(['related_waybill_id']);
            $table->dropForeign(['parent_order_id']);
            $table->dropColumn(['perspective', 'related_waybill_id', 'parent_order_id']);
        });
    }
};
