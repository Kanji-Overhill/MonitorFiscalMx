<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the optional HTTP import endpoint. Disabled unless explicitly
 * enabled and given a secret via env — the console command `sat:import-69b`
 * is the primary, recommended way to run imports.
 */
class AuthenticateSatImportEndpoint
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('sat.import_endpoint.enabled')) {
            return response()->json(['message' => 'No encontrado.'], 404);
        }

        $secret = config('sat.import_endpoint.secret');
        $provided = $request->header('X-Import-Secret');

        if (! $secret || ! $provided || ! hash_equals($secret, $provided)) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        return $next($request);
    }
}
