<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\KubernetesService;
use Illuminate\Http\Request;

class ClusterStatusController extends Controller
{
    public function __invoke(Request $request, KubernetesService $kubernetes)
    {
        $validated = $request->validate([
            'env' => 'nullable|in:development,production',
        ]);

        return response()->json(
            $kubernetes->status($validated['env'] ?? 'development')
        );
    }
}