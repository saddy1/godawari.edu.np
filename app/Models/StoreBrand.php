<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreBrand extends Model
{
    protected $fillable = ['name', 'country', 'notes'];

    public function items()
    {
        return $this->hasMany(StoreItem::class, 'store_brand_id');
    }
}
