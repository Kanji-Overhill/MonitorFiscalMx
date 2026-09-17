<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\VendorCheck;
use App\Services\Sat\RfcNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RfcHistoryController extends Controller
{
    public function __invoke(Request $request, string $rfc, RfcNormalizer $normalizer): JsonResponse
    {
        $request->validate([
            'zoho_vendor_id' => ['nullable', 'string', 'max:64'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var Organization $organization */
        $organization = $request->attributes->get('organization');

        $normalizedRfc = $normalizer->normalize($rfc);

        $query = VendorCheck::query()
            ->where('organization_id', $organization->id)
            ->where('rfc_normalized', $normalizedRfc)
            ->orderByDesc('checked_at');

        if ($vendorId = $request->query('zoho_vendor_id')) {
            $query->where('zoho_vendor_id', $vendorId);
        }

        $history = $query->limit((int) $request->query('limit', 20))->get();

        return response()->json([
            'rfc' => $normalizedRfc,
            'history' => $history->map(fn (VendorCheck $check) => [
                'checked_at' => $check->checked_at->toIso8601String(),
                'status' => $check->result_status,
                'matched' => $check->matched,
                'zoho_vendor_id' => $check->zoho_vendor_id,
                'snapshot' => $check->result_snapshot,
            ])->values(),
        ]);
    }
}
