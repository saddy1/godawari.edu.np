<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryRule extends Model
{
    protected $fillable = ['key', 'label', 'value', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
