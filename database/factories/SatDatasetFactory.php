<?php

namespace Database\Factories;

use App\Enums\DatasetStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class SatDatasetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => '69b',
            'source_url' => 'https://example.test/listado_69b.csv',
            'source_filename' => 'listado_69b.csv',
            'source_updated_at' => now()->toDateString(),
            'downloaded_at' => now(),
            'checksum' => $this->faker->sha256(),
            'status' => DatasetStatus::Active,
            'record_count' => 0,
            'error_message' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => DatasetStatus::Active]);
    }

    public function failed(): static
    {
        return $this->state(['status' => DatasetStatus::Failed]);
    }
}
