<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class MonitorQueue extends Command
{
    protected $signature = 'queue:monitor
                            {queues=default : Queues to monitor (comma separated)}
                            {--max=100 : Maximum allowed jobs in queue}';

    protected $description = 'Monitor queue sizes and alert if thresholds exceeded';

    public function handle()
    {
        $queues = explode(',', $this->argument('queues'));
        $maxJobs = (int) $this->option('max');

        $results = [];
        $alerts = [];

        foreach ($queues as $queue) {
            $queue = trim($queue);
            $jobCount = Redis::command('LLEN', ["queues:{$queue}"]);

            $results[] = [
                'queue' => $queue,
                'jobs' => $jobCount,
                'status' => $jobCount > $maxJobs ? '⚠️ HIGH' : '✅ OK'
            ];

            if ($jobCount > $maxJobs) {
                $alerts[] = "Queue '{$queue}' has {$jobCount} jobs (max: {$maxJobs})";
            }
        }

        // Log results
        Log::channel('queue')->info('Queue monitoring results', [
            'results' => $results,
            'alerts' => $alerts
        ]);

        // Display results
        $this->table(['Queue', 'Jobs', 'Status'], $results);

        // Send alerts if any
        if (!empty($alerts)) {
            foreach ($alerts as $alert) {
                $this->error($alert);
            }
            return 1;
        }

        $this->info('All queues are within acceptable limits.');
        return 0;
    }
}
