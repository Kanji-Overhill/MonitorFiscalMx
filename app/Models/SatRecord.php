<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id',
        'rfc_normalized',
        'business_name',
        'classification',
        'official_document',
        'publication_date',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'publication_date' => 'date',
            'raw_data' => 'array',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(SatDataset::class, 'dataset_id');
    }
}
