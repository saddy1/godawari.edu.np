<?php

namespace App\Models\TeachingLearning;

use App\Models\Card\Student;
use App\Models\Card\SubjectOffering;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RoutineLessonGroup extends Model
{
    protected $fillable = ['routine_lesson_id', 'subject_offering_id', 'teacher_id', 'routine_room_id', 'position', 'group_label', 'student_percentage'];
    protected $casts = ['student_percentage' => 'decimal:2'];

    public function lesson() { return $this->belongsTo(RoutineLesson::class, 'routine_lesson_id'); }
    public function offering() { return $this->belongsTo(SubjectOffering::class, 'subject_offering_id'); }
    public function teacher() { return $this->belongsTo(User::class); }
    public function teachers() { return $this->belongsToMany(User::class, 'routine_lesson_group_teachers', 'routine_lesson_group_id', 'teacher_id')->withTimestamps(); }
    public function room() { return $this->belongsTo(RoutineRoom::class, 'routine_room_id'); }
    public function students() { return $this->belongsToMany(Student::class, 'routine_lesson_group_students')->withTimestamps(); }

    public function getTeacherInitialsAttribute(): string
    {
        $teachers = $this->relationLoaded('teachers') && $this->teachers->isNotEmpty() ? $this->teachers : collect([$this->teacher])->filter();
        return $teachers->map(fn ($teacher) => collect(preg_split('/\s+/', trim((string) $teacher->name)))
            ->filter()->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->take(3)->implode(''))->implode('+');
    }

    public function getTeacherNamesAttribute(): string
    {
        $teachers = $this->relationLoaded('teachers') && $this->teachers->isNotEmpty() ? $this->teachers : collect([$this->teacher])->filter();

        return $teachers->pluck('name')->implode(', ');
    }
}
