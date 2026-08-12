<?php

namespace App\Models;

use App\Models\Card\Student;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'member_id', 'certificate_type', 'certificate_number',
        'exam_name', 'division_gpa', 'pass_year_bs', 'pass_year_ad',
        'character_description', 'symbol_no', 'issue_date',
        'student_name', 'parent_name', 'address', 'registration_no', 'gender',
        'issued_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
    ];

    public function member(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Student::class, 'member_id');
    }

    public function issuedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    // Gender-based pronoun helpers
    public function getPronounHeAttribute(): string
    {
        return strtolower($this->gender ?? '') === 'female' ? 'She' : 'He';
    }

    public function getPronounHisAttribute(): string
    {
        return strtolower($this->gender ?? '') === 'female' ? 'Her' : 'His';
    }

    public function getPronounHimAttribute(): string
    {
        return strtolower($this->gender ?? '') === 'female' ? 'her' : 'him';
    }

    public function getPronounHeSheAttribute(): string
    {
        return strtolower($this->gender ?? '') === 'female' ? 'she' : 'he';
    }

    public function getSonDaughterAttribute(): string
    {
        return strtolower($this->gender ?? '') === 'female' ? 'Daughter' : 'Son';
    }

    public function getMrMsAttribute(): string
    {
        return strtolower($this->gender ?? '') === 'female' ? 'Ms.' : 'Mr.';
    }

    public function getCertificateTypeLabelAttribute(): string
    {
        return $this->certificate_type === 'provisional'
            ? 'Provisional Certificate'
            : 'Character/Transfer Certificate';
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return 'CERT-' . $year . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
