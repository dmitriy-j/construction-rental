<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('news', 'published_at')) {
            Schema::table('news', function (Blueprint $table) {
                $table->timestamp('published_at')->nullable();
            });
        }
        if (!Schema::hasColumn('news', 'views_count')) {
            Schema::table('news', function (Blueprint $table) {
                $table->integer('views_count')->default(0);
            });
        }
        if (!Schema::hasColumn('news', 'created_by')) {
            Schema::table('news', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->constrained('users');
            });
        }
        if (!Schema::hasColumn('news', 'excerpt')) {
            Schema::table('news', function (Blueprint $table) {
                $table->text('excerpt')->nullable();
            });
        }
    }

    public function down(): void
    {
        //
    }
};
