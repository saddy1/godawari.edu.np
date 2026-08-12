<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreRequisitionItem extends Model
{
    protected $fillable = [
        'store_requisition_id',
        'store_item_id',
        'store_category_id',
        'item_name',
        'specification',
        'unit',
        'quantity',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(StoreItem::class, 'store_item_id');
    }

    public function category()
    {
        return $this->belongsTo(StoreCategory::class, 'store_category_id');
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(StorePurchaseOrderItem::class, 'store_requisition_item_id');
    }

    public function getOrderedQuantityAttribute(): float
    {
        return (float) $this->purchaseOrderItems()->sum('quantity');
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max((float) $this->quantity - $this->ordered_quantity, 0);
    }
}
