<?php
// database/migrations/2024_01_01_create_proposal_templates_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProposalTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('proposal_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('equipment_categories'); // Указание правильной таблицы
            $table->decimal('proposed_price', 10, 2); // соответствует proposed_price в rental_request_responses
            $table->integer('response_time')->default(24); // в часах
            $table->text('message'); // соответствует message в rental_request_responses
            $table->json('price_breakdown')->nullable(); // соответствует price_breakdown
            $table->json('additional_terms')->nullable(); // соответствует additional_terms
            $table->integer('usage_count')->default(0);
            $table->decimal('success_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable(); // дополнительные условия
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('proposal_templates');
    }
}
