<?php

namespace App\Models\TeachingLearning;

use App\Models\Card\Department;
use App\Models\Card\Organization;
use Illuminate\Database\Eloquent\Model;

class RoutineShiftAssignment extends Model
{
    protected $fillable = ['academic_year_id', 'routine_shift_id', 'organization_id', 'department_id'];

    public function shift()
    {
        return $this->belongsTo(RoutineShift::class, 'routine_shift_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
