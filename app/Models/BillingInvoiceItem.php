<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingInvoiceItem extends Model
{
    protected $fillable = [
        'billing_invoice_id',
        'description',
        'quantity',
        'rate',
        'amount',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(BillingInvoice::class, 'billing_invoice_id');
    }
}
