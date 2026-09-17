<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DatasetStatus;
use App\Http\Controllers\Controller;
use App\Models\SatDataset;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $activeDataset = SatDataset::query()
            ->where('type', '69b')
            ->where('status', DatasetStatus::Active)
            ->latest('downloaded_at')
            ->first();

        return response()->json([
            'status' => 'ok',
            'time' => now()->toIso8601String(),
            'dataset' => [
                'available' => (bool) $activeDataset,
                'updated_at' => $activeDataset?->source_updated_at?->toDateString(),
                'record_count' => $activeDataset?->record_count,
            ],
        ]);
    }
}
