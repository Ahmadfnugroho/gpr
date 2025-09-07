<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QueueMonitorController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'recent_jobs' => DB::table('jobs')
                ->select('queue', 'payload', 'attempts', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    return [
                        'queue' => $job->queue,
                        'job' => $payload['displayName'] ?? 'Unknown Job',
                        'attempts' => $job->attempts,
                        'created_at' => Carbon::parse($job->created_at)->diffForHumans(),
                    ];
                }),
            'recent_failed' => DB::table('failed_jobs')
                ->select('queue', 'payload', 'exception', 'failed_at')
                ->orderBy('failed_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    return [
                        'queue' => $job->queue,
                        'job' => $payload['displayName'] ?? 'Unknown Job',
                        'exception' => substr($job->exception, 0, 100) . '...',
                        'failed_at' => Carbon::parse($job->failed_at)->diffForHumans(),
                    ];
                }),
        ];

        return response()->json($stats);
    }
}
