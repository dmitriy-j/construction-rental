<?php

use App\Models\Upd;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('upds', function (Blueprint $table) {
            $table->enum('type', [Upd::TYPE_INCOMING, Upd::TYPE_OUTGOING])
                ->default(Upd::TYPE_INCOMING)
                ->after('status');
        });

        // Расширяем enum статусов для добавления 'processed'
        DB::statement("ALTER TABLE upds MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected', 'processed') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('upds', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        DB::statement("ALTER TABLE upds MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
