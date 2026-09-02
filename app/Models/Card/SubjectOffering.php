<?php

namespace App\Models\Card;

use Illuminate\Database\Eloquent\Model;

class SubjectOffering extends Model
{
    protected $fillable = ['department_id', 'group_name', 'semester', 'year_level', 'subject_id', 'is_elective', 'elective_group'];
    protected $casts = ['is_elective' => 'boolean'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
