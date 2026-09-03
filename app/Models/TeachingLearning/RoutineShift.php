<?php

namespace App\Models\TeachingLearning;

use App\Models\Card\Department;
use App\Models\Card\Organization;
use Illuminate\Database\Eloquent\Model;

class RoutineShift extends Model
{
    protected $fillable = [
        'academic_year_id', 'organization_id', 'name', 'starts_at', 'ends_at',
        'period_minutes', 'break_after_period', 'break_minutes', 'working_days',
        'is_active', 'is_locked',
    ];

    protected $casts = [
        'working_days' => 'array',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function periods()
    {
        return $this->hasMany(RoutinePeriod::class)->orderBy('position');
    }

    public function assignments()
    {
        return $this->hasMany(RoutineShiftAssignment::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'routine_shift_assignments')
            ->withPivot('academic_year_id')->withTimestamps()->orderBy('name');
    }

    public static function resolveAssignedSlot(int $academicYearId, int $organizationId, ?int $departmentId = null): ?self
    {
        if ($departmentId) {
            $departmentSlot = static::query()
                ->where('is_active', true)
                ->whereHas('assignments', fn ($query) => $query
                    ->where('academic_year_id', $academicYearId)
                    ->where('organization_id', $organizationId)
                    ->where('department_id', $departmentId))
                ->first();

            if ($departmentSlot) return $departmentSlot;
        }

        return static::query()
            ->where('is_active', true)
            ->whereHas('assignments', fn ($query) => $query
                ->where('academic_year_id', $academicYearId)
                ->where('organization_id', $organizationId)
                ->whereNull('department_id'))
            ->first();
    }
}
