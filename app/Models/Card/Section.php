<?php

namespace App\Models\Card;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['department_id', 'name', 'group_name', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Not a true Eloquent relation (can't be eager-loaded) — a section's
    // offerings depend on both its department AND its own group_name.
    public function subjectOfferingsQuery()
    {
        return SubjectOffering::where('department_id', $this->department_id)
            ->where(function ($query) {
                $query->whereNull('group_name')->orWhere('group_name', $this->group_name);
            });
    }
}
