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

    // Students link via section_id where backfilled, otherwise by the legacy
    // organization/stream/section text triplet — not a true Eloquent relation.
    public function studentsQuery()
    {
        $department = $this->department;

        return Student::where('section_id', $this->id)
            ->orWhere(function ($query) use ($department) {
                $query->where('organization', $department->organization->slug)
                    ->where('stream', $department->name)
                    ->where('section', $this->name);
            });
    }
}
