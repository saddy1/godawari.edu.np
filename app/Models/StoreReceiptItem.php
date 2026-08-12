<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreReceiptItem extends Model
{
    protected $fillable = [
        'store_receipt_id',
        'store_purchase_order_item_id',
        'store_item_id',
        'item_name',
        'specification',
        'unit',
        'quantity',
        'rate',
        'amount',
        'condition',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(StoreItem::class, 'store_item_id');
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(StorePurchaseOrderItem::class, 'store_purchase_order_item_id');
    }
}
