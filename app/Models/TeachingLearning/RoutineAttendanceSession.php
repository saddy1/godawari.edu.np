<?php

namespace App\Models\TeachingLearning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RoutineAttendanceSession extends Model
{
    protected $fillable = ['routine_lesson_id', 'attendance_date', 'opened_by', 'opened_at', 'submitted_at'];
    protected $casts = ['attendance_date' => 'date', 'opened_at' => 'datetime', 'submitted_at' => 'datetime'];

    public function lesson() { return $this->belongsTo(RoutineLesson::class, 'routine_lesson_id'); }
    public function attendances() { return $this->hasMany(RoutineStudentAttendance::class); }
    public function openedBy() { return $this->belongsTo(User::class, 'opened_by'); }
}
