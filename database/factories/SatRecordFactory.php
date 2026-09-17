<?php

namespace Database\Factories;

use App\Models\SatDataset;
use Illuminate\Database\Eloquent\Factories\Factory;

class SatRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'dataset_id' => SatDataset::factory(),
            'rfc_normalized' => 'AAA010101AAA',
            'business_name' => $this->faker->company(),
            'classification' => 'definitivo',
            'official_document' => '500-05-00-2026-00000 01/01/2026',
            'publication_date' => now()->toDateString(),
            'raw_data' => ['RFC' => 'AAA010101AAA'],
        ];
    }
}
