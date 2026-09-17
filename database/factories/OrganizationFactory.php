<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'zoho_organization_id' => (string) $this->faker->unique()->numerify('##########'),
            'name' => $this->faker->company(),
            'status' => 'active',
            'api_token' => Hash::make('testing-secret'),
            'settings' => null,
        ];
    }

    /**
     * Returns the plaintext bearer token matching the hash set by definition().
     * Use together with a freshly created (not persisted-then-refetched-elsewhere) organization.
     */
    public static function plainToken(): string
    {
        return 'testing-secret';
    }
}
