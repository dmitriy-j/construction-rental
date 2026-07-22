<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DropNewsTable extends Command
{
    protected $signature = 'db:drop-news-table';
    protected $description = 'Drop the news table if exists';

    public function handle()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('DROP TABLE IF EXISTS news');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->info('News table dropped successfully');
        return Command::SUCCESS;
    }
}
