<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreFiscalYear extends Model
{
    protected $fillable = [
        'name',
        'starts_on_bs',
        'ends_on_bs',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
