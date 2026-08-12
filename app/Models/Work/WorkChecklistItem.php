<?php

namespace App\Models\Work;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_checklist_id',
        'title',
        'description',
        'category',
        'max_score',
        'incentive_amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'incentive_amount' => 'decimal:2',
        ];
    }

    public function checklist()
    {
        return $this->belongsTo(WorkChecklist::class, 'work_checklist_id');
    }
}
