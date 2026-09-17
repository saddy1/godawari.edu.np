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
    // Set by sync() when the member's email couldn't be used for login because
    // another account already owns it — a fallback login email was used instead.
    public ?string $lastEmailConflictMessage = null;

    public function sync(Student $member, ?string $password = null, ?string $loginUserId = null, ?string $loginEmail = null): User
    {
        $this->lastEmailConflictMessage = null;

        $loginCode = trim($loginUserId ?: $member->user?->student_code ?: $member->roll_number);
        $fallbackEmail = strtolower($loginCode) . '@' . $member->member_type . '.local';

        $user = $member->user ?: User::where('student_code', $loginCode)->first();

        // An explicit login email (admin override) takes priority over the
        // member's contact email for what actually gets used to sign in.
        $desiredEmail = filled($loginEmail) ? trim($loginEmail) : $member->email;
        $email = $desiredEmail ?: $fallbackEmail;

        if ($desiredEmail) {
            $emailOwner = User::where('email', $desiredEmail)
                ->when($user?->id, fn ($q) => $q->where('id', '!=', $user->id))
                ->first();

            if ($emailOwner) {
                $email = $fallbackEmail;
                $this->lastEmailConflictMessage = "\"{$desiredEmail}\" is already used by another account ({$emailOwner->name}), so a placeholder login email ({$fallbackEmail}) was used instead. Resolve the conflict (e.g. delete or change the other account's email) and save again to use the real email for login.";
            } else {
                $email = $desiredEmail;
            }
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
