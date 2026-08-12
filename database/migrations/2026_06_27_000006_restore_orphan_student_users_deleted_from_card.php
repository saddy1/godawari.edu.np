<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('students')) {
            return;
        }

        $studentRoleId = DB::table('roles')->where('name', 'student')->where('guard_name', 'web')->value('id');
        if (! $studentRoleId) {
            return;
        }

        $defaultOrganization = DB::table('organizations')->where('slug', 'school')->value('slug')
            ?: DB::table('organizations')->orderBy('id')->value('slug')
            ?: 'school';

        $users = DB::table('users')
            ->join('model_has_roles', function ($join) use ($studentRoleId) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', App\Models\User::class)
                    ->where('model_has_roles.role_id', $studentRoleId);
            })
            ->leftJoin('students', 'students.user_id', '=', 'users.id')
            ->leftJoin('organizations', 'organizations.id', '=', 'users.organization_id')
            ->whereNull('students.id')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.student_code',
                'users.class_grade',
                'users.section',
                'users.phone',
                'users.province',
                'users.district',
                'users.municipal',
                'organizations.slug as organization_slug',
            ])
            ->orderBy('users.id')
            ->get();

        foreach ($users as $user) {
            $rollNumber = trim((string) ($user->student_code ?: 'USR-'.$user->id));
            $existing = DB::table('students')->where('roll_number', $rollNumber)->first();

            if ($existing) {
                DB::table('students')->where('id', $existing->id)->update([
                    'user_id' => $user->id,
                    'updated_at' => now(),
                ]);
                continue;
            }

            [$firstName, $middleName, $lastName] = $this->splitName((string) $user->name);

            DB::table('students')->insert([
                'user_id' => $user->id,
                'organization' => $user->organization_slug ?: $defaultOrganization,
                'member_type' => 'student',
                'roll_number' => $rollNumber,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'email' => $user->email,
                'mobile' => $user->phone,
                'program' => $user->class_grade,
                'stream' => $user->class_grade,
                'section' => $user->section,
                'zone' => $user->province,
                'district' => $user->district,
                'municipality' => $user->municipal,
                'permanent_province' => $user->province,
                'permanent_district' => $user->district,
                'permanent_municipality' => $user->municipal,
                'country' => 'Nepal',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Data restoration only. Do not delete restored HR/member records.
    }

    private function splitName(string $name): array
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($name)) ?: []));

        if (count($parts) === 0) {
            return ['Student', null, 'User'];
        }

        if (count($parts) === 1) {
            return [$parts[0], null, 'User'];
        }

        $first = array_shift($parts);
        $last = array_pop($parts);
        $middle = $parts ? Str::limit(implode(' ', $parts), 255, '') : null;

        return [
            Str::limit($first, 255, ''),
            $middle,
            Str::limit($last ?: 'User', 255, ''),
        ];
    }
};
