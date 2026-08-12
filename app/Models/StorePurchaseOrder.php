<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorePurchaseOrder extends Model
{
    protected $fillable = [
        'order_no',
        'store_requisition_id',
        'store_supplier_id',
        'supplier_name',
        'supplier_address',
        'supplier_phone',
        'fiscal_year',
        'decision_no',
        'decision_date',
        'decision_date_bs',
        'order_date',
        'order_date_bs',
        'expected_date',
        'expected_date_bs',
        'tax_mode',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'decision_date' => 'date',
        'order_date' => 'date',
        'expected_date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(StorePurchaseOrderItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(StoreSupplier::class, 'store_supplier_id');
    }

    public function requisition()
    {
        return $this->belongsTo(StoreRequisition::class, 'store_requisition_id');
    }
}
