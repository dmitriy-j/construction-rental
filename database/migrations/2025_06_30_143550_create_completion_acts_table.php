<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompletionActsTable extends Migration
{
    public function up()
    {
        Schema::create('completion_acts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->date('act_date')->comment('Дата составления акта');
            $table->decimal('total_hours', 10, 2);
            $table->decimal('total_downtime', 10, 2)->default(0);
            $table->decimal('penalty_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('prepayment_amount', 12, 2)->default(0);
            $table->decimal('final_amount', 12, 2);
            $table->string('act_file_path')->nullable();
            $table->enum('status', ['draft', 'signed_lessor', 'signed_lessee', 'completed'])->default('draft');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('completion_acts');
    }
}
