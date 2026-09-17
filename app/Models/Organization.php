<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'zoho_organization_id',
        'name',
        'status',
        'api_token',
        'settings',
    ];

    protected $hidden = [
        'api_token',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function vendorChecks(): HasMany
    {
        return $this->hasMany(VendorCheck::class);
    }
}
