<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorRfcOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'zoho_vendor_id',
        'rfc_normalized',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
