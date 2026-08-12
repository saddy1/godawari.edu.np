<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreIssueItem extends Model
{
    protected $fillable = [
        'store_issue_id',
        'store_item_id',
        'item_name',
        'specification',
        'unit',
        'quantity',
        'returned_quantity',
        'returned_at_bs',
        'returned_at',
        'returned_by',
        'rate',
        'amount',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'returned_quantity' => 'decimal:2',
        'returned_at' => 'datetime',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(StoreItem::class, 'store_item_id');
    }

    public function issue()
    {
        return $this->belongsTo(StoreIssue::class, 'store_issue_id');
    }

    public function getIsReturnedAttribute(): bool
    {
        return (float) $this->returned_quantity >= (float) $this->quantity;
    }
}
