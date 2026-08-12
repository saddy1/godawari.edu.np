<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreItem extends Model
{
    protected $fillable = [
        'store_category_id',
        'store_brand_id',
        'store_unit_id',
        'item_code',
        'name',
        'specification',
        'model_no',
        'serial_no',
        'asset_type',
        'min_stock',
        'opening_quantity',
        'opening_rate',
        'current_quantity',
        'current_value',
        'storage_location',
        'useful_life_months',
        'depreciation_rate',
        'is_active',
    ];

    protected $casts = [
        'min_stock' => 'decimal:2',
        'opening_quantity' => 'decimal:2',
        'opening_rate' => 'decimal:2',
        'current_quantity' => 'decimal:2',
        'current_value' => 'decimal:2',
        'depreciation_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(StoreCategory::class, 'store_category_id');
    }

    public function brand()
    {
        return $this->belongsTo(StoreBrand::class, 'store_brand_id');
    }

    public function unit()
    {
        return $this->belongsTo(StoreUnit::class, 'store_unit_id');
    }

    public function movements()
    {
        return $this->hasMany(StoreStockMovement::class);
    }

    public function getAverageRateAttribute(): float
    {
        $quantity = (float) $this->current_quantity;

        return $quantity > 0 ? round((float) $this->current_value / $quantity, 2) : (float) $this->opening_rate;
    }
}
