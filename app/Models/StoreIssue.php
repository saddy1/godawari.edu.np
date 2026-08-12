<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreIssue extends Model
{
    protected $fillable = [
        'issue_no',
        'store_requisition_id',
        'issued_to_name',
        'issued_to_designation',
        'purpose',
        'fiscal_year',
        'issued_at',
        'issued_at_bs',
        'approved_by_name',
        'store_keeper_name',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'issued_at' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(StoreIssueItem::class);
    }
}
