<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProvisionOrganizationCommand extends Command
{
    protected $signature = 'organizations:provision {zoho_organization_id} {name?}';

    protected $description = 'Registra una organización de Zoho Books y genera su token de API (se muestra una sola vez).';

    public function handle(): int
    {
        $zohoOrganizationId = $this->argument('zoho_organization_id');

        if (Organization::where('zoho_organization_id', $zohoOrganizationId)->exists()) {
            $this->error("Ya existe una organización registrada con zoho_organization_id={$zohoOrganizationId}.");

            return self::FAILURE;
        }

        $plainToken = Str::random(40);

        $organization = Organization::create([
            'zoho_organization_id' => $zohoOrganizationId,
            'name' => $this->argument('name'),
            'status' => 'active',
            'api_token' => Hash::make($plainToken),
        ]);

        $this->info("Organización #{$organization->id} creada.");
        $this->newLine();
        $this->warn('Token de API (guárdalo ahora, no se volverá a mostrar):');
        $this->line("{$organization->id}|{$plainToken}");
        $this->newLine();
        $this->line('Configura este valor como el header Authorization: Bearer <token> de la API Configuration en Zoho Sigma.');

        return self::SUCCESS;
    }
}
