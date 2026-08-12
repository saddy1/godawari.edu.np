<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreStockMovement extends Model
{
    protected $fillable = [
        'store_item_id',
        'source_type',
        'source_id',
        'movement_type',
        'movement_date',
        'movement_date_bs',
        'fiscal_year',
        'quantity_in',
        'quantity_out',
        'rate',
        'amount',
        'balance_quantity',
        'balance_value',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'quantity_in' => 'decimal:2',
        'quantity_out' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'balance_quantity' => 'decimal:2',
        'balance_value' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(StoreItem::class, 'store_item_id');
    }
}
