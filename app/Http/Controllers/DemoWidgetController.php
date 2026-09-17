<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Página de desarrollo que simula el widget de Zoho Books directamente en el
 * navegador, llamando a nuestra propia API vía fetch() (sin SDK de Zoho).
 * Sirve para probar el backend y el aspecto visual del widget sin depender
 * de una cuenta real de Zoho Books. Solo disponible en entorno local.
 */
class DemoWidgetController extends Controller
{
    public function index(): View
    {
        if (! app()->environment('local')) {
            throw new NotFoundHttpException;
        }

        $organization = Organization::firstOrCreate(
            ['zoho_organization_id' => 'demo-widget'],
            ['name' => 'Demo widget (local)', 'status' => 'active'],
        );

        $plainToken = Str::random(40);
        $organization->update(['api_token' => Hash::make($plainToken)]);

        return view('demo.index', [
            'bearerToken' => "{$organization->id}|{$plainToken}",
        ]);
    }
}
