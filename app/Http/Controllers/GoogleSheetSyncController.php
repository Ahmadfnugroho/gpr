<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Customer\CustomerSyncService;
use App\Services\CustomerSheetSyncService;
use Illuminate\Http\JsonResponse;

class GoogleSheetSyncController
{
    public function sync(
        Request $request,
        CustomerSheetSyncService $service
    ): JsonResponse {
        $payload = $request->json()->all();

        // 1. Validate contract
        if (
            !isset($payload['meta'], $payload['values']) ||
            count($payload['values']) < 2
        ) {
            return response()->json([
                'status' => 'error',
                'code' => 'INVALID_PAYLOAD',
                'message' => 'Invalid payload structure',
            ], 400);
        }

        $headers = array_map('trim', $payload['values'][0]);
        $rows = array_slice($payload['values'], 1);

        // 2. Delegate to service
        $result = $service->sync($headers, $rows);

        return response()->json([
            'status' => 'success',
            'meta' => [
                'synced_at' => now()->toISOString(),
                'total_rows' => count($rows),
                'processed' => $result['processed'],
                'skipped' => $result['skipped'],
            ],
        ]);
    }
}
