<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSupplier extends Model
{
    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'tax_registration_type',
        'pan_vat_no',
        'registration_no',
        'address',
        'bank_name',
        'bank_account_name',
        'bank_account_no',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function purchaseOrders()
    {
        return $this->hasMany(StorePurchaseOrder::class, 'store_supplier_id');
    }

    public function receipts()
    {
        return $this->hasMany(StoreReceipt::class, 'store_supplier_id');
    }
}
