<?php

namespace App\Models\TeachingLearning;

use App\Models\Card\Department;
use App\Models\Card\Organization;
use App\Models\Card\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RoutinePlan extends Model
{
    protected $fillable = ['academic_year_id', 'organization_id', 'department_id', 'routine_shift_id', 'name', 'semester', 'year_level', 'status', 'created_by'];

    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function organization() { return $this->belongsTo(Organization::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function shift() { return $this->belongsTo(RoutineShift::class, 'routine_shift_id'); }
    public function sections() { return $this->belongsToMany(Section::class, 'routine_plan_sections')->withTimestamps(); }
    public function lessons() { return $this->hasMany(RoutineLesson::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function getIsLockedAttribute(): bool { return $this->status === 'published' || (bool) $this->academicYear?->is_locked; }
}
