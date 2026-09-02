<?php

namespace App\Models\Card;

use Illuminate\Database\Eloquent\Model;
use App\Models\Card\Department;
use App\Models\Card\Organization;

class Student extends Model
{
protected $fillable = [
        'user_id', 'organization', 'member_type', 'roll_number', 'registration_no',
        'first_name', 'middle_name', 'last_name', 'guardian_name',
        'father_name', 'mother_name', 'grandfather_name',
        'guardian_relation', 'guardian_contact',
        'dob', 'dob_bs', 'gender', 'blood_group', 'citizenship_no',
        'joining_date_bs', 'permanent_date_bs', 'valid_till_bs',
        'mobile', 'parent_contact', 'emergency_contact_name', 'emergency_contact_phone',
        'email', 'photo',
        'designation', 'employment_type', 'valid_till',
        'employee_category', 'joining_date', 'permanent_date',
        'bank_name', 'bank_branch', 'bank_account_name', 'bank_account_number',
        'pan_number', 'ssf_number', 'cit_number',
        'program', 'stream', 'section', 'section_id', 'batch', 'semester', 'year_level',
        'zone', 'district', 'municipality', 'country',
        'permanent_province', 'permanent_district', 'permanent_municipality', 'permanent_ward', 'permanent_tole',
        'address_en',
        'temporary_province', 'temporary_district', 'temporary_municipality', 'temporary_ward', 'temporary_tole',
        'bus_route', 'bus_stop', 'has_bus_pass',
        'library_id', 'has_library_card', 'profile_completed_at', 'card_printed_at',
    ];
    protected $casts = [
        'dob'          => 'date',
        'valid_till'   => 'date',
        'joining_date' => 'date',
        'permanent_date' => 'date',
        'has_bus_pass' => 'boolean',
        'has_library_card' => 'boolean',
        'profile_completed_at' => 'datetime',
        'card_printed_at'      => 'datetime',
    ];

    public function getFullNameAttribute(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name])
            ->map(fn ($part) => trim((string) $part))
            ->filter()
            ->implode(' ');
    }

    public function cardRequests()
    {
        return $this->hasMany(CardRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function updateRequests()
    {
        return $this->hasMany(UpdateRequest::class);
    }

    public function libraryLoans()
    {
        return $this->hasMany(\App\Models\LibraryLoan::class, 'student_id');
    }

    public function getPhotoUrlAttribute(): string
    {
        $version = $this->updated_at?->timestamp ?? 0;

        return $this->photo
            ? asset($this->photo) . '?v=' . $version
            : asset('images/default-avatar.svg');
    }

    public function getSectionLabelAttribute(): ?string
    {
        return $this->section;
    }

    public function getDesignationAttribute($value): ?string
    {
        return $value ?: ($this->user?->designation?->label ?? null);
    }

    public function getEmploymentTypeAttribute($value): ?string
    {
        return $value ?: ($this->user?->employment?->label ?? null);
    }

    public function getDepartmentLabelAttribute(): ?string
    {
        return $this->stream ?: $this->program;
    }

    public function getDepartmentRecordAttribute(): ?Department
    {
        if (!$this->stream) return null;

        return Department::whereHas('organization', fn($q) => $q->where('slug', $this->organization))
            ->where('name', $this->stream)
            ->first();
    }

    public function getOrganizationRecordAttribute(): ?Organization
    {
        return Organization::with(['logoAsset', 'signatureAsset', 'stampAsset'])
            ->where('slug', $this->organization)
            ->first();
    }

    // Named section master-list record, once staff assign one via Bulk Edit.
    // Not yet backfilled for existing students — see `section` for the legacy text value.
    public function academicSection()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function subjectEnrollments()
    {
        return $this->hasMany(StudentSubjectEnrollment::class);
    }

    public function getAddressLabelAttribute(): ?string
    {
        $parts = array_filter([
            $this->zone,
            $this->district,
            $this->municipality,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }
}
