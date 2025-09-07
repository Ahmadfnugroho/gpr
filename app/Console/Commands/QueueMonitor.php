<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QueueMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:monitor {--refresh=5 : Refresh interval in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor Laravel queue status in real-time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $refresh = (int) $this->option('refresh');
        
        $this->info('Laravel Queue Monitor Started');
        $this->info('Press Ctrl+C to stop');
        $this->newLine();
        
        while (true) {
            $this->clearScreen();
            $this->displayHeader();
            $this->displayStats();
            $this->displayRecentJobs();
            $this->displayFailedJobs();
            
            sleep($refresh);
        }
    }
    
    private function clearScreen()
    {
        // Clear screen (works on most terminals)
        echo "\033[2J\033[H";
    }
    
    private function displayHeader()
    {
        $this->info('=== Laravel Queue Monitor ===');
        $this->info('Last updated: ' . Carbon::now()->format('Y-m-d H:i:s'));
        $this->newLine();
    }
    
    private function displayStats()
    {
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        
        $this->table(
            ['Metric', 'Count', 'Status'],
            [
                ['Pending Jobs', $pendingJobs, $pendingJobs > 0 ? '⚠️  Processing' : '✅ Clear'],
                ['Failed Jobs', $failedJobs, $failedJobs > 0 ? '❌ Attention Needed' : '✅ Good'],
            ]
        );
        
        $this->newLine();
    }
    
    private function displayRecentJobs()
    {
        $recentJobs = DB::table('jobs')
            ->select('queue', 'payload', 'attempts', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        if ($recentJobs->count() > 0) {
            $this->info('Recent Pending Jobs:');
            
            $tableData = [];
            foreach ($recentJobs as $job) {
                $payload = json_decode($job->payload, true);
                $tableData[] = [
                    'Queue' => $job->queue,
                    'Job' => $payload['displayName'] ?? 'Unknown Job',
                    'Attempts' => $job->attempts,
                    'Created' => Carbon::parse($job->created_at)->diffForHumans(),
                ];
            }
            
            $this->table(['Queue', 'Job', 'Attempts', 'Created'], $tableData);
        } else {
            $this->info('✅ No pending jobs');
        }
        
        $this->newLine();
    }
    
    private function displayFailedJobs()
    {
        $failedJobs = DB::table('failed_jobs')
            ->select('queue', 'payload', 'exception', 'failed_at')
            ->orderBy('failed_at', 'desc')
            ->limit(3)
            ->get();
            
        if ($failedJobs->count() > 0) {
            $this->error('Recent Failed Jobs:');
            
            $tableData = [];
            foreach ($failedJobs as $job) {
                $payload = json_decode($job->payload, true);
                $tableData[] = [
                    'Queue' => $job->queue,
                    'Job' => $payload['displayName'] ?? 'Unknown Job',
                    'Error' => substr($job->exception, 0, 50) . '...',
                    'Failed' => Carbon::parse($job->failed_at)->diffForHumans(),
                ];
            }
            
            $this->table(['Queue', 'Job', 'Error', 'Failed'], $tableData);
        } else {
            $this->info('✅ No failed jobs');
        }
    }
}
