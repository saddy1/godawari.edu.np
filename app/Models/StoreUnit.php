<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreUnit extends Model
{
    protected $fillable = ['name', 'symbol', 'allow_decimal'];

    protected $casts = [
        'allow_decimal' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(StoreItem::class, 'store_unit_id');
    }
}
