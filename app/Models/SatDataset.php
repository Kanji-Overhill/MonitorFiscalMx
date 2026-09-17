<?php

namespace App\Models;

use App\Enums\DatasetStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SatDataset extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'source_url',
        'source_filename',
        'source_updated_at',
        'downloaded_at',
        'checksum',
        'status',
        'record_count',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'source_updated_at' => 'date',
            'downloaded_at' => 'datetime',
            'status' => DatasetStatus::class,
        ];
    }

    public function records(): HasMany
    {
        return $this->hasMany(SatRecord::class, 'dataset_id');
    }
}
