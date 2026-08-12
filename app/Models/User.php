<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'student_code',
        'class_grade',
        'section',
        'password',
        'phone',
        'is_active',
        'organization_id',
        'department_id',
        'hajiri_department_id',
        'designation_id',
        'employment_type_id',
        'work_assigned_id',
        'device_id',
        'province',
        'district',
        'municipal',
        'status',
        'sort',
        'google_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['administrator', 'super-admin', 'principal', 'accountant', 'store-keeper', 'librarian', 'technical']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * True only for the designated system owner — the single account that may
     * grant or revoke the super-admin role. Set APP_OWNER_EMAIL in .env.
     */
    public function isOwner(): bool
    {
        $ownerEmail = config('app.owner_email', env('APP_OWNER_EMAIL', ''));
        return $ownerEmail && strtolower($this->email) === strtolower($ownerEmail);
    }

    public function isPrincipal(): bool
    {
        return $this->hasRole('principal');
    }

    public function isAccountant(): bool
    {
        return $this->hasRole('accountant');
    }

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    public function isTeacher(): bool
    {
        return $this->hasRole('teacher');
    }

    public function canAccess(string|array $permissions): bool
    {
        $permissions = (array) $permissions;

        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->permissions()->exists()) {
            return $this->permissions()->whereIn('name', $permissions)->exists();
        }

        return $this->hasAnyPermission($permissions);
    }

    public function designation()
    {
        return $this->hasOne(\App\Models\Hajiri\Designation::class, 'id', 'designation_id');
    }

    public function employment()
    {
        return $this->hasOne(\App\Models\Hajiri\EmploymentType::class, 'id', 'employment_type_id');
    }

    public function working_at()
    {
        return $this->hasOne(\App\Models\Hajiri\WorkAssigned::class, 'id', 'work_assigned_id');
    }

    public function hajiriDepartment()
    {
        return $this->hasOne(\App\Models\Hajiri\Department::class, 'id', 'hajiri_department_id');
    }

    public function logs()
    {
        return $this->hasMany(\App\Models\Hajiri\AttendanceLogs::class, 'user_id', 'device_id');
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Card\Organization::class);
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Card\Department::class);
    }

    public function organizationSlug(): ?string
    {
        return $this->organization?->slug ?: $this->student?->organization;
    }

    public function organizationName(): ?string
    {
        return $this->organization?->name ?: $this->student?->organization_record?->name;
    }

    public function departmentName(): ?string
    {
        return $this->department?->name ?: $this->student?->stream;
    }

    public function applyStudentScope($query)
    {
        if ($this->isSuperAdmin()) {
            return $query;
        }

        if ($organizationSlug = $this->organizationSlug()) {
            $query->where('organization', $organizationSlug);
        }

        if ($departmentName = $this->departmentName()) {
            $query->where('stream', $departmentName);
        }

        return $query;
    }

    public function getRoleLabelAttribute(): string
    {
        if ($this->isSuperAdmin())           return 'Super Admin';
        if ($this->isPrincipal())            return 'Principal';
        if ($this->isAccountant())           return 'Accountant';
        if ($this->hasRole('administrator')) return 'Administrator';
        if ($this->hasRole('teacher'))       return 'Teacher';
        if ($this->hasRole('staff'))         return 'Staff';
        if ($this->isStudent())              return 'Student';
        return 'Employee';
    }

    public function applications()
    {
        return $this->hasMany(VacancyApplication::class);
    }

    public function workGroups()
    {
        return $this->belongsToMany(\App\Models\Work\WorkGroup::class, 'work_group_user')->withTimestamps();
    }

    public function assignedWorkTasks()
    {
        return $this->hasMany(\App\Models\Work\WorkTask::class, 'assigned_user_id');
    }

    public function workTaskSubmissions()
    {
        return $this->hasMany(\App\Models\Work\WorkTaskSubmission::class);
    }

    public function student()
    {
        return $this->hasOne(\App\Models\Card\Student::class);
    }

    public function learningTeacherClassMaps()
    {
        return $this->hasMany(\App\Models\Learning\LearningTeacherClassMap::class);
    }

    public function assignedLearningClasses()
    {
        return $this->belongsToMany(\App\Models\Learning\LearningClass::class, 'learning_teacher_class_maps')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function canManageLearningClass(?int $learningClassId): bool
    {
        if ($this->isSuperAdmin() || $this->isPrincipal() || $this->hasAnyRole(['administrator'])) {
            return true;
        }

        if (! $this->isTeacher() || ! $learningClassId) {
            return false;
        }

        return $this->assignedLearningClasses()
            ->where('learning_classes.id', $learningClassId)
            ->exists();
    }

    public function assignedLearningSubjects()
    {
        return $this->belongsToMany(\App\Models\Learning\LearningSubject::class, 'learning_teacher_subject_maps')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function canManageLearningCourse(\App\Models\Learning\LearningCourse $course): bool
    {
        if ($this->isSuperAdmin() || $this->isPrincipal() || $this->hasAnyRole(['administrator'])) {
            return true;
        }

        if (! $this->isTeacher()) {
            return false;
        }

        if (! $this->canManageLearningClass($course->learning_class_id)) {
            return false;
        }

        // If the course has a subject, teacher must also be assigned to that subject
        if ($course->learning_subject_id) {
            return $this->assignedLearningSubjects()
                ->where('learning_subjects.id', $course->learning_subject_id)
                ->exists();
        }

        return true;
    }
}
