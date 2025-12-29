<?php

namespace App\Http\Controllers;

use App\Services\CustomerSheetSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;

final class GoogleSheetSyncController
{
    public function __construct(
        protected CustomerSheetSyncService $sheetSyncService
    ) {}

    /**
     * POST /google-sheet-sync
     * Satu arah: Google Sheet → Database
     */
    public function sync(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $data = $request->json()->all();

        if (!isset($data['values']) || count($data['values']) < 2) {
            return response()->json(['error' => 'No data found'], 400);
        }

        $headers = array_map(fn($h) => trim($h), $data['values'][0]);
        $rows = array_slice($data['values'], 1);

        $lastSyncedAt = !empty($data['last_synced_at'])
            ? Carbon::parse($data['last_synced_at'])
            : now()->subYears(1);

        $batchSize = 100;

        $result = $this->sheetSyncService->sync(
            headers: $headers,
            rows: $rows,
            lastSyncedAt: $lastSyncedAt,
            batchSize: $batchSize
        );

        return response()->json([
            'processed' => $result['processed'],
            'skipped' => $result['skipped'],
            'synced_at' => $result['synced_at']->toIso8601String(),
        ]);
    }
}
