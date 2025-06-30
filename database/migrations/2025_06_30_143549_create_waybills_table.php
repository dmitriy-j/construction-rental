<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWaybillsTable extends Migration
{
    public function up()
    {
        Schema::create('waybills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('equipment_id')->constrained()->onDelete('cascade');
            $table->foreignId('operator_id')->constrained('users')->onDelete('cascade');
            $table->date('work_date');
            $table->decimal('hours_worked', 8, 2);
            $table->decimal('downtime_hours', 8, 2)->default(0);
            $table->enum('downtime_cause', ['lessee', 'lessor', 'force_majeure'])->nullable();
            $table->string('operator_signature_path')->nullable();
            $table->string('customer_signature_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waybills');
    }
}
