<?php

namespace App\Models\TeachingLearning;

use App\Models\Card\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RoutineStudentAttendance extends Model
{
    protected $fillable = [
        'routine_attendance_session_id', 'student_id', 'status', 'remarks', 'marked_by', 'marked_at',
        'notebook_checked', 'notebook_checked_by', 'notebook_checked_at',
    ];
    protected $casts = ['marked_at' => 'datetime', 'notebook_checked' => 'boolean', 'notebook_checked_at' => 'datetime'];

    public function session() { return $this->belongsTo(RoutineAttendanceSession::class, 'routine_attendance_session_id'); }
    public function student() { return $this->belongsTo(Student::class); }
    public function markedBy() { return $this->belongsTo(User::class, 'marked_by'); }
    public function notebookCheckedBy() { return $this->belongsTo(User::class, 'notebook_checked_by'); }
}
