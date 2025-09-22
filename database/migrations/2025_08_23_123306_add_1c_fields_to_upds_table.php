<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Add1CFieldsToUpdsTable extends Migration
{
    public function up()
    {
        Schema::table('upds', function (Blueprint $table) {
            $table->string('1c_guid')->nullable()->after('idempotency_key');
            $table->string('1c_number')->nullable()->after('1c_guid');
            $table->date('1c_date')->nullable()->after('1c_number');
            $table->string('operation_type')->default('Услуги')->after('1c_date');
            $table->string('document_type')->default('УПД')->after('operation_type');
            $table->text('payment_conditions')->nullable()->after('document_type');
            $table->string('currency')->default('RUB')->after('payment_conditions');
            $table->decimal('currency_rate', 10, 4)->default(1)->after('currency');
            $table->boolean('vat_included')->default(true)->after('currency_rate');

            // Поля для подписей
            $table->string('lessor_sign_position')->nullable()->after('vat_included');
            $table->string('lessor_sign_name')->nullable()->after('lessor_sign_position');
            $table->timestamp('lessor_sign_date')->nullable()->after('lessor_sign_name');
            $table->string('lessee_sign_position')->nullable()->after('lessor_sign_date');
            $table->string('lessee_sign_name')->nullable()->after('lessee_sign_position');
            $table->timestamp('lessee_sign_date')->nullable()->after('lessee_sign_name');
        });
    }

    public function down()
    {
        Schema::table('upds', function (Blueprint $table) {
            $table->dropColumn([
                '1c_guid',
                '1c_number',
                '1c_date',
                'operation_type',
                'document_type',
                'payment_conditions',
                'currency',
                'currency_rate',
                'vat_included',
                'lessor_sign_position',
                'lessor_sign_name',
                'lessor_sign_date',
                'lessee_sign_position',
                'lessee_sign_name',
                'lessee_sign_date',
            ]);
        });
    }
}
