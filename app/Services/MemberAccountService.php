<?php

namespace App\Services;

use App\Models\Card\Student;
use App\Models\Card\Department;
use App\Models\Card\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class MemberAccountService
{
    public function sync(Student $member, ?string $password = null, ?string $loginUserId = null): User
    {
        $loginCode = trim($loginUserId ?: $member->user?->student_code ?: $member->roll_number);
        $fallbackEmail = strtolower($loginCode) . '@' . $member->member_type . '.local';
        $email = $member->email ?: $fallbackEmail;

        $user = $member->user ?: User::where('student_code', $loginCode)->first();

        if ($member->email) {
            $emailOwner = User::where('email', $member->email)
                ->when($user?->id, fn ($q) => $q->where('id', '!=', $user->id))
                ->first();
            $email = $emailOwner ? $fallbackEmail : $member->email;
        }

        if (!$user) {
            $user = new User();
            $user->password = Hash::make($password ?: $loginCode);
        } elseif ($password) {
            $user->password = Hash::make($password);
        }

        $organization = filled($member->organization)
            ? Organization::where('slug', $member->organization)->first()
            : null;
        $department = $organization && filled($member->stream)
            ? Department::where('organization_id', $organization->id)
                ->where('name', $member->stream)
                ->first()
            : null;

        $user->fill([
            'name' => $member->full_name,
            'email' => $email,
            'student_code' => $loginCode,
            'class_grade' => $member->stream,
            'section' => $member->section,
            'organization_id' => $organization?->id,
            'department_id' => $department?->id,
            'phone' => $member->mobile,
            'province' => $member->permanent_province ?: $member->zone,
            'district' => $member->permanent_district ?: $member->district,
            'municipal' => $member->permanent_municipality ?: $member->municipality,
            'is_active' => true,
            'status' => 1,
        ]);
        $user->save();

        $role = $this->roleFor($member);
        Role::findOrCreate($role, 'web');

        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }

        if ((int) $member->user_id !== (int) $user->id) {
            $member->forceFill(['user_id' => $user->id])->save();
        }

        return $user;
    }

    public function supportsPortalAccount(Student $member): bool
    {
        return in_array($member->member_type, ['student', 'teacher', 'staff'], true);
    }

    private function roleFor(Student $member): string
    {
        return match ($member->member_type) {
            'teacher' => 'teacher',
            'staff' => 'staff',
            default => 'student',
        };
    }
}
