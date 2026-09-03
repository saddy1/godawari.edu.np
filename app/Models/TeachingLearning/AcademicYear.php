<?php

namespace App\Models\TeachingLearning;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = ['name', 'starts_on', 'ends_on', 'is_active', 'is_locked'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function shifts()
    {
        return $this->belongsToMany(RoutineShift::class, 'routine_shift_assignments')
            ->withPivot(['organization_id', 'department_id'])
            ->withTimestamps();
    }

    public function shiftAssignments()
    {
        return $this->hasMany(RoutineShiftAssignment::class);
    }
}
