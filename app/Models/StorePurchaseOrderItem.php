<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorePurchaseOrderItem extends Model
{
    protected $fillable = [
        'store_purchase_order_id',
        'store_item_id',
        'store_requisition_item_id',
        'store_category_id',
        'item_name',
        'specification',
        'unit',
        'quantity',
        'rate',
        'tax_rate',
        'amount',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(StoreItem::class, 'store_item_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(StorePurchaseOrder::class, 'store_purchase_order_id');
    }

    public function requisitionItem()
    {
        return $this->belongsTo(StoreRequisitionItem::class, 'store_requisition_item_id');
    }

    public function category()
    {
        return $this->belongsTo(StoreCategory::class, 'store_category_id');
    }

    public function receivedItems()
    {
        return $this->hasMany(StoreReceiptItem::class, 'store_purchase_order_item_id');
    }

    public function getReceivedQuantityAttribute(): float
    {
        if ($this->relationLoaded('receivedItems')) {
            return (float) $this->receivedItems->sum('quantity');
        }

        return (float) $this->receivedItems()->sum('quantity');
    }

    public function getRemainingQuantityAttribute(): float
    {
        return max((float) $this->quantity - $this->received_quantity, 0);
    }
}
