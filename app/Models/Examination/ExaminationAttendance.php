<?php

namespace App\Models\Examination;

use App\Models\Card\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExaminationAttendance extends Model
{
    protected $fillable = [
        'examination_id', 'examination_subject_id', 'student_id', 'exam_date',
        'status', 'reason', 'marked_by', 'marked_at',
    ];
    protected $casts = ['exam_date' => 'date', 'marked_at' => 'datetime'];

    public function examination() { return $this->belongsTo(Examination::class); }
    public function examinationSubject() { return $this->belongsTo(ExaminationSubject::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function markedBy() { return $this->belongsTo(User::class, 'marked_by'); }
}
