<?php

namespace App\Models\Examination;

use App\Models\Card\Department;
use App\Models\Card\Organization;
use App\Models\Card\Section;
use App\Models\TeachingLearning\AcademicYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Examination extends Model
{
    protected $fillable = ['academic_year_id', 'organization_id', 'scope_type', 'department_id', 'name', 'category', 'semester', 'year_level', 'starts_on', 'ends_on', 'starts_at', 'theory_duration_minutes', 'practical_duration_minutes', 'practical_enabled', 'status', 'notes', 'created_by', 'symbol_numbers_locked_at', 'symbol_numbers_locked_by'];
    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'practical_enabled' => 'boolean', 'symbol_numbers_locked_at' => 'datetime'];

    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function organization() { return $this->belongsTo(Organization::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function departments() { return $this->belongsToMany(Department::class, 'examination_departments')->withTimestamps(); }
    public function sections() { return $this->belongsToMany(Section::class, 'examination_sections')->withTimestamps(); }
    public function subjects() { return $this->hasMany(ExaminationSubject::class); }
    public function symbolNumbers() { return $this->hasMany(ExaminationSymbolNumber::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function symbolNumbersLockedBy() { return $this->belongsTo(User::class, 'symbol_numbers_locked_by'); }
    public function getIsLockedAttribute(): bool { return in_array($this->status, ['completed', 'published'], true); }
    // Separate from the exam's own completed/published lock above — this one
    // specifically guards symbol-number (re)generation, once admit cards start
    // going out, without freezing anything else about the exam.
    public function getSymbolNumbersLockedAttribute(): bool { return $this->symbol_numbers_locked_at !== null; }
}
