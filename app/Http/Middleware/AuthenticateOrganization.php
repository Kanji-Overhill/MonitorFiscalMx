<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates a request from the Zoho Books widget/extension.
 *
 * Expects `Authorization: Bearer {organization_id}|{plaintext_token}`. The
 * plaintext token is only ever generated once (see OrganizationProvisioner)
 * and stored hashed; it is meant to be configured server-side as a header in
 * a Zoho API Configuration, never embedded in the widget's JavaScript.
 */
class AuthenticateOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->bearerToken();

        if (! $header || ! str_contains($header, '|')) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        [$organizationId, $plainToken] = explode('|', $header, 2);

        $organization = Organization::find($organizationId);

        if (! $organization || ! $organization->api_token || $organization->status !== 'active') {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        if (! Hash::check($plainToken, $organization->api_token)) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $request->attributes->set('organization', $organization);

        return $next($request);
    }
}
