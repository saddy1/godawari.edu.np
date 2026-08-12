<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingInvoice extends Model
{
    protected $fillable = [
        'bill_no',
        'type',
        'party_source_type',
        'party_source_id',
        'party_name',
        'party_identifier',
        'party_phone',
        'party_email',
        'party_address',
        'purpose',
        'payment_method',
        'reference_no',
        'subtotal',
        'discount',
        'tax',
        'total',
        'amount_words',
        'notes',
        'created_by',
        'issued_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(BillingInvoiceItem::class)->orderBy('sort_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'payment' ? 'Payment Voucher' : 'Cash Receipt';
    }
}
