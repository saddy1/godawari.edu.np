<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreCategory extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'is_consumable',
        'description',
    ];

    protected $casts = [
        'is_consumable' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function items()
    {
        return $this->hasMany(StoreItem::class, 'store_category_id');
    }
}
