<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingCatalogItem extends Model
{
    protected $fillable = [
        'name',
        'default_rate',
        'is_active',
    ];

    protected $casts = [
        'default_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
