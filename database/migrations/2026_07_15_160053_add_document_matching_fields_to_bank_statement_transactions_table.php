<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statement_transactions', function (Blueprint $table) {
            $table->string('document_type', 50)->nullable()->after('status')
                  ->comment('Тип привязанного документа: upd, invoice, order, contract');
            $table->unsignedBigInteger('document_id')->nullable()->after('document_type')
                  ->comment('ID привязанного документа');
            $table->unsignedBigInteger('matched_company_id')->nullable()->after('document_id')
                  ->comment('ID найденного контрагента (компании)');
            $table->boolean('is_unmatched')->default(false)->after('matched_company_id')
                  ->comment('Флаг: не удалось сопоставить');
            $table->string('unmatched_reason', 255)->nullable()->after('is_unmatched')
                  ->comment('Причина отсутствия сопоставления');

            $table->index('document_type');
            $table->index('document_id');
            $table->index('matched_company_id');
            $table->index('is_unmatched');
        });
    }

    public function down(): void
    {
        Schema::table('bank_statement_transactions', function (Blueprint $table) {
            $table->dropIndex(['document_type']);
            $table->dropIndex(['document_id']);
            $table->dropIndex(['matched_company_id']);
            $table->dropIndex(['is_unmatched']);
            $table->dropColumn([
                'document_type',
                'document_id',
                'matched_company_id',
                'is_unmatched',
                'unmatched_reason',
            ]);
        });
    }
};
