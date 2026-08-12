<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreRequisition extends Model
{
    protected $fillable = [
        'requisition_no',
        'requested_by_name',
        'requested_by_designation',
        'purpose',
        'fiscal_year',
        'status',
        'requested_at',
        'requested_at_bs',
        'approved_at',
        'approved_at_bs',
        'approved_by_name',
        'created_by',
    ];

    protected $casts = [
        'requested_at' => 'date',
        'approved_at' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(StoreRequisitionItem::class);
    }

    public function getDisplayRequisitionNoAttribute(): string
    {
        preg_match('/(\d+)$/', (string) $this->requisition_no, $matches);
        $sequence = (int) ($matches[1] ?? $this->id);

        return 'REQ-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
