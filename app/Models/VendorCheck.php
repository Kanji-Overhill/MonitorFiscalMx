<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'zoho_vendor_id',
        'rfc_normalized',
        'result_status',
        'matched',
        'result_snapshot',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'matched' => 'boolean',
            'result_snapshot' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
