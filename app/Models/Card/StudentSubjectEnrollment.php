<?php

namespace App\Models\Card;

use Illuminate\Database\Eloquent\Model;

class StudentSubjectEnrollment extends Model
{
    protected $fillable = ['student_id', 'subject_id', 'academic_year'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
