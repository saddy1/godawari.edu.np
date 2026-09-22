<?php

namespace App\Models\Card;

use Illuminate\Database\Eloquent\Model;

class SectionLabGroup extends Model
{
    protected $fillable = ['section_id', 'name'];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'lab_group_id');
    }
}
