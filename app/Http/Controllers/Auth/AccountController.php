<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Card\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function editProfile()
    {
        $user = auth()->user();
        abort_unless($user?->isTeacher(), 403);

        $member = Student::query()->where('user_id', $user->id)->first();

        return view('auth.teacher-profile', compact('user', 'member'));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->isTeacher(), 403);

        // Identity, access, organization and employment fields are deliberately
        // absent: name, email, device_id and organization cannot be self-edited.
        $data = $request->validate([
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'phone' => ['nullable', 'string', 'max:30'],
            'dob_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'gender' => ['nullable', 'in:Male,Female,Other'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'address_en' => ['nullable', 'string', 'max:255'],
            'permanent_province' => ['nullable', 'string', 'max:100'],
            'permanent_district' => ['nullable', 'string', 'max:100'],
            'permanent_municipality' => ['nullable', 'string', 'max:150'],
            'permanent_ward' => ['nullable', 'string', 'max:20'],
            'permanent_tole' => ['nullable', 'string', 'max:150'],
            'temporary_province' => ['nullable', 'string', 'max:100'],
            'temporary_district' => ['nullable', 'string', 'max:100'],
            'temporary_municipality' => ['nullable', 'string', 'max:150'],
            'temporary_ward' => ['nullable', 'string', 'max:20'],
            'temporary_tole' => ['nullable', 'string', 'max:150'],
        ]);

        $member = Student::query()->where('user_id', $user->id)->first();
        $photo = $data['photo'] ?? null;
        unset($data['photo']);

        if ($photo && $member) {
            File::ensureDirectoryExists(public_path('profile-photos'));
            $filename = Str::uuid().'.'.strtolower($photo->getClientOriginalExtension());
            $photo->move(public_path('profile-photos'), $filename);
            $data['photo'] = 'profile-photos/'.$filename;
        }

        DB::transaction(function () use ($user, $member, $data) {
            $user->update([
                'phone' => $data['phone'] ?? null,
                'province' => $data['permanent_province'] ?? null,
                'district' => $data['permanent_district'] ?? null,
                'municipal' => $data['permanent_municipality'] ?? null,
            ]);

            if ($member) {
                $memberData = array_merge($data, [
                    'mobile' => $data['phone'] ?? null,
                    'zone' => $data['permanent_province'] ?? null,
                    'district' => $data['permanent_district'] ?? null,
                    'municipality' => $data['permanent_municipality'] ?? null,
                ]);
                unset($memberData['phone']);
                $member->update($memberData);
            }
        });

        return back()->with('status', 'Your profile details were updated.');
    }

    public function editPassword()
    {
        if (auth()->user()?->isTeacher() && \App\Services\ModuleService::enabled('teaching_learning')) {
            return view('auth.teacher-change-password');
        }

        return view('auth.change-password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('status', 'Password updated successfully.');
    }
}
