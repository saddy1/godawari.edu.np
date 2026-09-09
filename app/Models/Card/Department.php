<?php

namespace App\Models\Card;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['organization_id', 'name', 'academic_system', 'school_class', 'university', 'university_college', 'university_logo', 'is_active'];
    protected $casts = ['is_active' => 'boolean', 'school_class' => 'integer'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class)->orderBy('name');
    }

    public function activeSections()
    {
        return $this->sections()->where('is_active', true);
    }

    // Students link by organization slug + department name, not a foreign key — not a true Eloquent relation.
    public function studentsQuery()
    {
        return Student::where('organization', $this->organization->slug)
            ->where('stream', $this->name);
    }
}
