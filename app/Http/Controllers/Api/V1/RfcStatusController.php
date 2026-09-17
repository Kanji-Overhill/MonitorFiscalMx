<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\VendorCheck;
use App\Services\Sat\Sat69BLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RfcStatusController extends Controller
{
    public function __invoke(Request $request, string $rfc, Sat69BLookupService $lookupService): JsonResponse
    {
        $request->validate([
            'zoho_vendor_id' => ['nullable', 'string', 'max:64'],
        ]);

        /** @var Organization $organization */
        $organization = $request->attributes->get('organization');

        $result = $lookupService->lookup($rfc);

        VendorCheck::create([
            'organization_id' => $organization->id,
            'zoho_vendor_id' => $request->query('zoho_vendor_id'),
            'rfc_normalized' => $result['rfc'],
            'result_status' => $result['status'],
            'matched' => $result['matched'],
            'result_snapshot' => $result,
            'checked_at' => now(),
        ]);

        return response()->json($result);
    }
}
