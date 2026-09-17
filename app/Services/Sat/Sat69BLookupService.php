<?php

namespace App\Services\Sat;

use App\Enums\DatasetStatus;
use App\Enums\FiscalStatus;
use App\Models\SatDataset;
use App\Models\SatRecord;
use Illuminate\Support\Collection;

class Sat69BLookupService
{
    public function __construct(
        private readonly RfcNormalizer $normalizer,
        private readonly RfcValidator $validator,
    ) {}

    /**
     * @return array<string, mixed> response payload following the stable /rfcs/{rfc}/status contract.
     */
    public function lookup(string $rawRfc): array
    {
        $rfc = $this->normalizer->normalize($rawRfc);

        if (! $this->validator->isStructurallyValid($rfc)) {
            return $this->buildResponse($rfc, valid: false, status: FiscalStatus::RfcInvalido, matched: false);
        }

        $dataset = $this->activeDataset();

        if (! $dataset) {
            return $this->buildResponse($rfc, valid: true, status: FiscalStatus::FuenteNoDisponible, matched: false);
        }

        $matches = SatRecord::query()
            ->where('dataset_id', $dataset->id)
            ->where('rfc_normalized', $rfc)
            ->orderByRaw('publication_date IS NULL, publication_date DESC')
            ->get();

        if ($matches->isEmpty()) {
            return $this->buildResponse($rfc, valid: true, status: FiscalStatus::SinCoincidencia, matched: false, dataset: $dataset);
        }

        $topStatus = FiscalStatus::fromClassification($matches->first()->classification);

        return $this->buildResponse(
            $rfc,
            valid: true,
            status: $topStatus,
            matched: true,
            dataset: $dataset,
            matches: $matches,
        );
    }

    private function activeDataset(): ?SatDataset
    {
        return SatDataset::query()
            ->where('type', '69b')
            ->where('status', DatasetStatus::Active)
            ->latest('downloaded_at')
            ->first();
    }

    /**
     * @param  Collection<int, SatRecord>|null  $matches
     * @return array<string, mixed>
     */
    private function buildResponse(
        string $rfc,
        bool $valid,
        FiscalStatus $status,
        bool $matched,
        ?SatDataset $dataset = null,
        ?Collection $matches = null,
    ): array {
        return [
            'rfc' => $rfc,
            'valid' => $valid,
            'matched' => $matched,
            'status' => $status->value,
            'status_label' => $status->label(),
            'checked_at' => now()->toIso8601String(),
            'source' => [
                'name' => config('sat.source_name'),
                'dataset_updated_at' => $dataset?->source_updated_at?->toDateString(),
                'official_url' => config('sat.official_url'),
            ],
            'matches' => $matches
                ? $matches->map(fn (SatRecord $record) => [
                    'business_name' => $record->business_name,
                    'classification' => $record->classification,
                    'publication_date' => $record->publication_date?->toDateString(),
                    'official_document' => $record->official_document,
                ])->values()->all()
                : [],
            'disclaimer' => config('sat.disclaimer'),
        ];
    }
}
