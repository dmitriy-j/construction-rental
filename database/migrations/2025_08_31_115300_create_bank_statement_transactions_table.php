<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bank_statement_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained()->onDelete('cascade');
            $table->string('currency', 3)->default('RUB');
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['incoming', 'outgoing']);
            $table->string('payer_name');
            $table->string('payer_inn');
            $table->string('payer_account');
            $table->string('payer_bic');
            $table->string('payee_name');
            $table->string('payee_inn');
            $table->string('payee_account');
            $table->string('payee_bic');
            $table->text('purpose');
            $table->string('idempotency_key')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['bank_statement_id', 'status']);
            $table->index(['date', 'type']);
            $table->index('payer_inn');
            $table->index('payee_inn');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bank_statement_transactions');
    }
};
