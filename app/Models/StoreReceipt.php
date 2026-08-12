<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreReceipt extends Model
{
    protected $fillable = [
        'receipt_no',
        'store_purchase_order_id',
        'store_supplier_id',
        'received_from',
        'challan_no',
        'invoice_no',
        'invoice_date',
        'invoice_date_bs',
        'fiscal_year',
        'received_at',
        'received_at_bs',
        'received_by_name',
        'verified_by_name',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'received_at' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(StoreReceiptItem::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(StorePurchaseOrder::class, 'store_purchase_order_id');
    }
}
