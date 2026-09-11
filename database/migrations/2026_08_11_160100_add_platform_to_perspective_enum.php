<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE waybills MODIFY perspective ENUM('lessor','lessee','platform') NOT NULL DEFAULT 'lessor'");
        DB::statement("ALTER TABLE completion_acts MODIFY perspective ENUM('lessor','lessee','platform') NOT NULL DEFAULT 'lessor'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE waybills MODIFY perspective ENUM('lessor','lessee') NOT NULL DEFAULT 'lessor'");
        DB::statement("ALTER TABLE completion_acts MODIFY perspective ENUM('lessor','lessee') NOT NULL DEFAULT 'lessor'");
    }
};
