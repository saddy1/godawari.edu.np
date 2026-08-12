<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class StudentAccountController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q', ''));

        $students = User::role('student')
            ->whereHas('student', fn ($query) => $query->where('member_type', 'student'))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($studentQuery) use ($search) {
                    $studentQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('student_code', 'like', "%{$search}%")
                        ->orWhere('class_grade', 'like', "%{$search}%")
                        ->orWhere('section', 'like', "%{$search}%");
                });
            })
            ->orderBy('class_grade')
            ->orderBy('section')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        if ($request->ajax()) {
            return view('learning.admin.students.partials.table', compact('students'))->render();
        }

        return view('learning.admin.students.index', compact('students', 'search'));
    }

    public function store(Request $request)
    {
        if (\App\Services\ModuleService::enabled('hr') && $request->user()?->canAccess('hr.members.create')) {
            return redirect()->route('admin.hr.members.create')->with('success', 'Create student accounts from HR People Master. Learning will use the synced login.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'student_code' => ['required', 'string', 'max:80', 'unique:users,student_code'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'class_grade' => ['nullable', 'string', 'max:20'],
            'section' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $studentCode = trim($validated['student_code']);

        $student = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?: strtolower($studentCode) . '@student.local',
            'student_code' => $studentCode,
            'class_grade' => $validated['class_grade'] ?? null,
            'section' => $validated['section'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => true,
            'status' => 1,
        ]);

        Role::findOrCreate('student', 'web');
        $student->assignRole('student');

        return redirect()->route('admin.learning.students.index')->with('success', 'Student login account created.');
    }

    public function resetPassword(Request $request, User $student)
    {
        abort_unless($student->isStudent(), 404);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $student->forceFill(['password' => Hash::make($validated['password'])])->save();

        return back()->with('success', 'Student password updated.');
    }
}
