<?php

namespace App\Models\TeachingLearning;

use App\Models\Card\Section;
use Illuminate\Database\Eloquent\Model;

class RoutineLesson extends Model
{
    protected $fillable = ['routine_plan_id', 'section_id', 'routine_period_id', 'end_routine_period_id', 'day_of_week', 'mode', 'notes'];

    public function plan() { return $this->belongsTo(RoutinePlan::class, 'routine_plan_id'); }
    public function section() { return $this->belongsTo(Section::class); }
    public function period() { return $this->belongsTo(RoutinePeriod::class, 'routine_period_id'); }
    public function endPeriod() { return $this->belongsTo(RoutinePeriod::class, 'end_routine_period_id'); }
    public function groups() { return $this->hasMany(RoutineLessonGroup::class)->orderBy('position'); }
    public function attendanceSessions() { return $this->hasMany(RoutineAttendanceSession::class); }
}
