<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\VendorRfcOverride;
use App\Services\Sat\RfcNormalizer;
use App\Services\Sat\RfcValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * El SDK del widget no expone el RFC del proveedor (confirmado en Developer
 * Mode). El usuario lo captura manualmente una vez desde el widget; estos
 * endpoints lo guardan y lo recuerdan por proveedor dentro de su organización.
 */
class VendorRfcController extends Controller
{
    public function show(Request $request, string $zohoVendorId): JsonResponse
    {
        /** @var Organization $organization */
        $organization = $request->attributes->get('organization');

        $override = VendorRfcOverride::query()
            ->where('organization_id', $organization->id)
            ->where('zoho_vendor_id', $zohoVendorId)
            ->first();

        return response()->json([
            'zoho_vendor_id' => $zohoVendorId,
            'rfc' => $override?->rfc_normalized,
        ]);
    }

    public function store(
        Request $request,
        string $zohoVendorId,
        RfcNormalizer $normalizer,
        RfcValidator $validator,
    ): JsonResponse {
        $request->validate([
            'rfc' => ['required', 'string', 'max:20'],
        ]);

        $rfc = $normalizer->normalize($request->input('rfc'));

        if (! $validator->isStructurallyValid($rfc)) {
            return response()->json([
                'zoho_vendor_id' => $zohoVendorId,
                'rfc' => null,
                'error' => 'rfc_invalido',
                'message' => 'El RFC capturado no tiene un formato válido.',
            ], 422);
        }

        /** @var Organization $organization */
        $organization = $request->attributes->get('organization');

        VendorRfcOverride::updateOrCreate(
            ['organization_id' => $organization->id, 'zoho_vendor_id' => $zohoVendorId],
            ['rfc_normalized' => $rfc],
        );

        return response()->json([
            'zoho_vendor_id' => $zohoVendorId,
            'rfc' => $rfc,
        ]);
    }
}
