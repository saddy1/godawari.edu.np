<?php

namespace App\Models\Examination;

use App\Models\Card\Student;
use Illuminate\Database\Eloquent\Model;

class ExaminationSymbolNumber extends Model
{
    protected $fillable = ['examination_id', 'student_id', 'symbol_no'];

    public function examination()
    {
        return $this->belongsTo(Examination::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
