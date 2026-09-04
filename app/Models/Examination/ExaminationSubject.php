<?php

namespace App\Models\Examination;

use App\Models\Card\SubjectOffering;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExaminationSubject extends Model
{
    protected $fillable = [
        'examination_id', 'subject_offering_id', 'teacher_id', 'practical_teacher_id',
        'theory_full_marks', 'theory_pass_marks', 'practical_full_marks', 'practical_pass_marks',
        'exam_date', 'starts_at', 'duration_minutes',
        'practical_exam_date', 'practical_starts_at', 'practical_duration_minutes',
    ];
    protected $casts = [
        'exam_date' => 'date', 'practical_exam_date' => 'date',
        'theory_full_marks' => 'decimal:2', 'theory_pass_marks' => 'decimal:2',
        'practical_full_marks' => 'decimal:2', 'practical_pass_marks' => 'decimal:2',
    ];

    public function examination() { return $this->belongsTo(Examination::class); }
    public function offering() { return $this->belongsTo(SubjectOffering::class, 'subject_offering_id'); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }
    public function practicalTeacher() { return $this->belongsTo(User::class, 'practical_teacher_id'); }
    public function marks() { return $this->hasMany(ExaminationMark::class); }
    public function getTotalFullMarksAttribute(): float { return (float) $this->theory_full_marks + (float) $this->practical_full_marks; }
}
