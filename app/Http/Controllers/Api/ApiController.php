<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Base class for the Phase 1 API route structure.
 *
 * Phase 1 (Routing Migration) only establishes the REST surface that future
 * phases will fill in. To avoid duplicating or moving business logic out of the
 * Livewire components prematurely (that work belongs to Phase 4: API Endpoint
 * Migration), every endpoint currently returns a uniform "not implemented"
 * contract. This keeps the routes real, authenticated and testable today while
 * documenting exactly where each Livewire method will land.
 */
abstract class ApiController extends Controller
{
    /**
     * Uniform 501 response describing an endpoint that is routed but whose
     * logic is scheduled for a later migration phase.
     *
     * @param  string  $source  The Livewire component/method that owns the logic today.
     */
    protected function pendingMigration(string $feature, string $source, string $phase = 'Phase 4'): JsonResponse
    {
        return response()->json([
            'status' => 'not_implemented',
            'feature' => $feature,
            'message' => "The {$feature} endpoint is registered but not yet implemented. Logic still lives in {$source} and will be migrated in {$phase}.",
            'migration' => [
                'phase' => $phase,
                'source' => $source,
            ],
        ], 501);
    }
}
