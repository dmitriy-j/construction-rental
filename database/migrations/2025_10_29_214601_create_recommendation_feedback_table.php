<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecommendationFeedbackTable extends Migration
{
    public function up()
    {
        Schema::create('recommendation_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->constrained('proposal_templates')->onDelete('cascade');
            $table->foreignId('request_id')->constrained('rental_requests')->onDelete('cascade');
            $table->boolean('applied')->default(false);
            $table->boolean('converted')->default(false);
            $table->decimal('score', 5, 2)->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->unique(['user_id', 'template_id', 'request_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('recommendation_feedback');
    }
}
