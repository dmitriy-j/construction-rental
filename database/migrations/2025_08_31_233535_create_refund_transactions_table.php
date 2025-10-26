<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRefundTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('refund_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained()->onDelete('cascade');
            $table->string('company_inn');
            $table->string('company_name');
            $table->decimal('amount', 15, 2);
            $table->string('type'); // refund_incoming/refund_outgoing
            $table->text('purpose');
            $table->json('transaction_data');
            $table->string('status'); // pending, processed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('refund_transactions');
    }
}
