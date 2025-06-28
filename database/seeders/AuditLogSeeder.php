<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AuditLog;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        AuditLog::factory(50)->create();
    }
}
