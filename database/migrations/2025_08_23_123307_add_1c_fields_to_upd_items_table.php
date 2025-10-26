<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Add1CFieldsToUpdItemsTable extends Migration
{
    public function up()
    {
        Schema::table('upd_items', function (Blueprint $table) {
            $table->string('1c_guid')->nullable()->after('upd_id');
            $table->string('nomenclature_code')->nullable()->after('1c_guid');
            $table->string('nomenclature_type')->default('Услуга')->after('nomenclature_code');
            $table->string('accounting_account')->nullable()->after('nomenclature_type');
            $table->string('vat_account')->nullable()->after('accounting_account');
            $table->string('cost_item')->nullable()->after('vat_account');
        });
    }

    public function down()
    {
        Schema::table('upd_items', function (Blueprint $table) {
            $table->dropColumn([
                '1c_guid',
                'nomenclature_code',
                'nomenclature_type',
                'accounting_account',
                'vat_account',
                'cost_item',
            ]);
        });
    }
}
