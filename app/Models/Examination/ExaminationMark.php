<?php

namespace App\Models\Examination;

use App\Models\Card\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExaminationMark extends Model
{
    protected $fillable = ['examination_subject_id', 'student_id', 'theory_marks', 'practical_marks', 'theory_is_absent', 'practical_is_absent', 'is_absent', 'remarks', 'entered_by', 'submitted_at'];
    protected $casts = ['theory_marks' => 'decimal:2', 'practical_marks' => 'decimal:2', 'theory_is_absent' => 'boolean', 'practical_is_absent' => 'boolean', 'is_absent' => 'boolean', 'submitted_at' => 'datetime'];

    public function examinationSubject() { return $this->belongsTo(ExaminationSubject::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function enteredBy() { return $this->belongsTo(User::class, 'entered_by'); }

    public function getObtainedMarksAttribute(): float
    {
        return ($this->theory_is_absent ? 0 : (float) ($this->theory_marks ?? 0))
            + ($this->practical_is_absent ? 0 : (float) ($this->practical_marks ?? 0));
    }
}
