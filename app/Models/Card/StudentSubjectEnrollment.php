<?php

namespace App\Models\Card;

use Illuminate\Database\Eloquent\Model;

class StudentSubjectEnrollment extends Model
{
    protected $fillable = ['student_id', 'subject_id', 'subject_offering_id', 'academic_year', 'assignment_source'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function offering()
    {
        return $this->belongsTo(SubjectOffering::class, 'subject_offering_id');
    }
}
