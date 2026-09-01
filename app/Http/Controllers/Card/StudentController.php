<?php

namespace App\Http\Controllers\Card;

use App\Http\Controllers\Controller;

use App\Models\Card\Organization;
use App\Models\Card\Student;
use App\Models\LibraryLoan;
use App\Services\MemberAccountService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StudentController extends Controller
{
    protected function scopedStudentsQuery(array $ids = [])
    {
        $query = Student::query()->with(['updateRequests' => fn ($query) => $query
            ->where('status', 'pending')
            ->latest()]);

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        }

        return $query;
    }

    protected function deleteStudentPhoto(?string $photoPath): void
    {
        if (!$photoPath || !str_starts_with($photoPath, 'photos/')) {
            return;
        }

        if (Student::where('photo', $photoPath)->count() > 1) {
            return;
        }

        $absolutePath = public_path($photoPath);

        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }
    }

    protected function hasActiveLibraryLoans(Student $student): bool
    {
        return LibraryLoan::where('status', 'issued')
            ->where(function ($query) use ($student) {
                $query->where('student_id', $student->id);
                if ($student->user_id) {
                    $query->orWhere('user_id', $student->user_id);
                }
            })
            ->exists();
    }

    protected function deleteStudentRecord(Student $student): void
    {
        DB::transaction(function () use ($student) {
            $student->loadMissing('user.roles');
            $linkedUser = $student->user;
            $shouldDeleteLinkedStudentLogin = $student->member_type === 'student'
                && $linkedUser
                && $linkedUser->hasRole('student')
                && ! $linkedUser->isSuperAdmin();

            $photoPath = $student->photo;
            $student->delete();
            $this->deleteStudentPhoto($photoPath);

            if ($shouldDeleteLinkedStudentLogin) {
                $linkedUser->delete();
            }
        });
    }

    public function index(Request $request)
    {
        $query = Student::query();
        $filterOptions = $this->buildFormOptions();

        if ($request->filled('search')) {
            $search = preg_replace('/\s+/', ' ', trim((string) $request->input('search')));
            $nameTerms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

            $query->where(function ($q) use ($search, $nameTerms) {
                $q->whereRaw(
                    "CONCAT_WS(' ', NULLIF(TRIM(first_name), ''), NULLIF(TRIM(middle_name), ''), NULLIF(TRIM(last_name), '')) LIKE ?",
                    ["%{$search}%"]
                )
                    ->orWhere(function ($nameQuery) use ($nameTerms) {
                        foreach ($nameTerms as $term) {
                            $nameQuery->where(function ($partQuery) use ($term) {
                                $partQuery->where('first_name', 'like', "%{$term}%")
                                    ->orWhere('middle_name', 'like', "%{$term}%")
                                    ->orWhere('last_name', 'like', "%{$term}%");
                            });
                        }
                    })
                    ->orWhere('roll_number', 'like', "%{$search}%")
                    ->orWhere('registration_no', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('member_type', $request->type);
        }

        if ($request->filled('stream')) {
            $query->where('stream', $request->stream);
        }

        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }

        $expiredCount = (clone $query)
            ->whereNotNull('valid_till')
            ->whereDate('valid_till', '<', now()->toDateString())
            ->count();

        $allowedPerPage = [10, 20, 40, 100];
        $perPageParam = $request->per_page ?? '';

        if ($perPageParam === 'all') {
            $perPage = max(1, (clone $query)->count());
        } elseif (in_array((int) $perPageParam, $allowedPerPage, true)) {
            $perPage = (int) $perPageParam;
        } else {
            $perPage = 20;
        }

        $students = $query
            ->orderByRaw('CASE WHEN member_type = ? THEN 0 WHEN member_type = ? THEN 1 ELSE 2 END', ['student', 'teacher'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('card.students.index', compact('students', 'filterOptions', 'expiredCount'));
    }

    public function create()
    {
        if (\App\Services\ModuleService::enabled('hr') && auth()->user()?->canAccess('hr.members.create')) {
            return redirect()->route('admin.hr.members.create');
        }

        return view('card.students.create', [
            'formOptions' => $this->buildFormOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'organization'    => 'required',
            'member_type'     => 'required|in:student,teacher,staff',
            'stream'          => 'nullable|string|max:100',
            'section'         => 'nullable|string|max:50',
            'roll_number'     => [
                'required',
                // Unique within the same org + stream + section group
                Rule::unique('students')->where(fn ($q) => $q
                    ->where('organization', $request->organization)
                    ->where('stream', $request->stream ?: null)
                    ->where('section', $request->section ?: null)
                ),
            ],
            'first_name'      => 'required|string|max:100',
            'middle_name'     => 'nullable|string|max:100',
            'last_name'       => 'required|string|max:100',
            'guardian_name'   => 'nullable|string|max:150',
            'registration_no' => 'nullable|string|max:100',
            'dob'             => 'nullable|date',
            'citizenship_no'  => 'nullable|string|max:50',
            'mobile'          => 'nullable|string|max:20',
            'email'           => 'nullable|email',
            'photo'           => 'nullable|image|max:' . ($request->member_type === 'student' ? '200' : '2048'),
            'designation'     => 'nullable|string|max:100',
            'employment_type' => 'nullable|string|max:100',
            'valid_till'      => ['nullable', Rule::requiredIf($request->member_type === 'student'), 'date'],
            'program'         => 'nullable|string|max:100',
            'batch'           => 'nullable|string|max:20',
            'zone'            => 'nullable|string|max:50',
            'district'        => 'nullable|string|max:50',
            'municipality'    => 'nullable|string|max:100',
            'bus_route'       => 'nullable|string|max:100',
            'bus_stop'        => 'nullable|string|max:100',
            'has_bus_pass'    => 'boolean',
            'library_id'      => 'nullable|string|max:50',
            'has_library_card'=> 'boolean',
            'create_learning_account' => 'boolean',
            'learning_password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        if ($request->hasFile('photo')) {
            $file      = $request->file('photo');
            $ext       = $file->getClientOriginalExtension();
            $filename  = $data['roll_number'] . '.' . $ext;
            $destPath  = public_path('photos/' . $filename);
            $file->move(public_path('photos'), $filename);
            $this->resizeImage($destPath, 400);
            $data['photo'] = "photos/{$filename}";
        }

        $data['has_bus_pass']     = $request->boolean('has_bus_pass');
        $data['has_library_card'] = $request->boolean('has_library_card');

        if ($data['has_library_card'] && empty($data['library_id'])) {
            $data['library_id'] = 'LIB-' . strtoupper(substr($data['member_type'], 0, 3)) . '-' . str_pad(Student::count() + 1, 4, '0', STR_PAD_LEFT);
        }

        $createLearningAccount = $request->boolean('create_learning_account') && in_array($data['member_type'], ['student', 'teacher'], true);
        $learningPassword = $request->input('learning_password');
        unset($data['create_learning_account'], $data['learning_password']);

        DB::transaction(function () use ($data, $createLearningAccount, $learningPassword) {
            $student = Student::create($data);

            if ($createLearningAccount) {
                app(MemberAccountService::class)->sync($student, $learningPassword);
            }
        });

        return redirect()->route('students.index')
                         ->with('success', 'Member added successfully!');
    }

    public function show(Student $student)
    {
        return view('card.students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        return view('card.students.create', [
            'student' => $student,
            'formOptions' => $this->buildFormOptions(),
        ]);
    }

    public function update(Request $request, Student $student)
    {
        if ($student->member_type === 'student') {
            $request->merge([
                'organization' => $student->organization,
                'member_type' => $student->member_type,
                'stream' => $student->stream,
                'section' => $student->section,
                'roll_number' => $student->roll_number,
            ]);
        }

        $data = $request->validate([
            'organization'    => 'required|string|max:100',
            'member_type'     => 'required|in:student,teacher,staff',
            'stream'          => 'nullable|string|max:100',
            'section'         => 'nullable|string|max:50',
            'roll_number'     => [
                'required',
                Rule::unique('students')->ignore($student->id)->where(fn ($q) => $q
                    ->where('organization', $request->organization)
                    ->where('stream', $request->stream ?: null)
                    ->where('section', $request->section ?: null)
                ),
            ],
            'first_name'      => 'required|string|max:100',
            'middle_name'     => 'nullable|string|max:100',
            'last_name'       => 'required|string|max:100',
            'guardian_name'   => 'nullable|string|max:150',
            'registration_no' => 'nullable|string|max:100',
            'dob'             => 'nullable|date',
            'citizenship_no'  => 'nullable|string|max:50',
            'mobile'          => 'nullable|string|max:20',
            'email'           => 'nullable|email',
            'photo'           => 'nullable|image|max:' . ($request->member_type === 'student' ? '200' : '2048'),
            'designation'     => 'nullable|string|max:100',
            'employment_type' => 'nullable|string|max:100',
            'valid_till'      => ['nullable', Rule::requiredIf($request->member_type === 'student'), 'date'],
            'program'         => 'nullable|string|max:100',
            'batch'           => 'nullable|string|max:20',
            'zone'            => 'nullable|string|max:50',
            'district'        => 'nullable|string|max:50',
            'municipality'    => 'nullable|string|max:100',
            'bus_route'       => 'nullable|string|max:100',
            'bus_stop'        => 'nullable|string|max:100',
            'has_bus_pass'    => 'boolean',
            'library_id'      => 'nullable|string|max:50',
            'has_library_card'=> 'boolean',
            'create_learning_account' => 'boolean',
            'learning_password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        if ($request->hasFile('photo')) {
            $file     = $request->file('photo');
            $ext      = $file->getClientOriginalExtension();
            $filename = $data['roll_number'] . '.' . $ext;
            $destPath = public_path('photos/' . $filename);
            $file->move(public_path('photos'), $filename);
            $this->resizeImage($destPath, 400);
            $data['photo'] = "photos/{$filename}";
        }

        $data['has_bus_pass']     = $request->boolean('has_bus_pass');
        $data['has_library_card'] = $request->boolean('has_library_card');

        $createLearningAccount = $request->boolean('create_learning_account') && in_array($data['member_type'], ['student', 'teacher'], true);
        $learningPassword = $request->input('learning_password');
        unset($data['create_learning_account'], $data['learning_password']);

        DB::transaction(function () use ($student, $data, $createLearningAccount, $learningPassword) {
            $student->update($data);

            if ($createLearningAccount || $student->user_id) {
                app(MemberAccountService::class)->sync($student->fresh(), $learningPassword);
            }
        });

        return redirect()->route('students.index')
                         ->with('success', 'Member updated successfully!');
    }

    public function bulkLearningAccounts(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:students,id'],
            'learning_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $students = $this->scopedStudentsQuery($data['ids'])
            ->whereIn('member_type', ['student', 'teacher'])
            ->get();

        $created = 0;

        DB::transaction(function () use ($students, $data, &$created) {
            foreach ($students as $student) {
                app(MemberAccountService::class)->sync($student, $data['learning_password']);
                $created++;
            }
        });

        return back()->with('success', "Learning login enabled for {$created} student(s).");
    }

    public function destroy(Student $student)
    {
        if ($this->hasActiveLibraryLoans($student)) {
            return back()->with('error', "\"{$student->full_name}\" has active library loans. Return all borrowed books before deleting.");
        }

        $this->deleteStudentRecord($student);

        return redirect()->route('students.index')
                         ->with('success', 'Member deleted.');
    }

    // ── Autocomplete suggestions ──────────────────────────────────────────
    public function suggestions(Request $request): JsonResponse
    {
        $allowed = ['program', 'stream', 'section', 'designation', 'employment_type', 'batch'];
        $field   = $request->input('field');

        if (!in_array($field, $allowed)) {
            return response()->json([]);
        }

        $values = Student::whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->orderBy($field)
            ->pluck($field);

        return response()->json($values);
    }

    public function formOptions(): JsonResponse
    {
        return response()->json($this->buildFormOptions());
    }

    public function bulkDestroy(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:students,id',
        ]);

        $students = Student::with('user.roles')->whereIn('id', $request->ids)->get();
        $count = $students->count();

        if ($count === 0) {
            return back()->with('error', 'No members selected for deletion.');
        }

        $blocked = $students->filter(fn ($student) => $this->hasActiveLibraryLoans($student));
        if ($blocked->isNotEmpty()) {
            return back()->with('error', 'Cannot delete the following members because they have active library loans: '.$blocked->pluck('full_name')->implode(', ').'. Return all borrowed books first.');
        }

        foreach ($students as $student) {
            $this->deleteStudentRecord($student);
        }

        return back()->with('success', "{$count} member(s) deleted successfully.");
    }

    protected function buildFormOptions(): array
    {
        if (Schema::hasTable('organizations') && Schema::hasTable('departments') && Schema::hasTable('sections')) {
            $organizations = Organization::where('is_active', true)
                ->with(['activeDepartments.activeSections'])
                ->orderBy('name')
                ->get();

            return $organizations->mapWithKeys(fn($organization) => [
                $organization->slug => [
                    'label' => $organization->name,
                    'streams' => $organization->activeDepartments
                        ->mapWithKeys(fn($department) => [
                            $department->name => $department->activeSections->pluck('name')->values()->all(),
                        ])
                        ->all(),
                ],
            ])->all();
        }

        $students = Student::query()
            ->select('organization', 'stream', 'section')
            ->whereNotNull('organization')
            ->where('organization', '!=', '')
            ->whereNotNull('stream')
            ->where('stream', '!=', '')
            ->where('member_type', 'student')
            ->orderBy('organization')
            ->orderBy('stream')
            ->orderBy('section')
            ->get();

        return $students
            ->groupBy('organization')
            ->map(function (Collection $organizationStudents, string $organization) {
                return [
                    'label' => ucfirst($organization),
                    'streams' => $organizationStudents
                        ->groupBy('stream')
                        ->map(fn(Collection $streamStudents) => $streamStudents
                            ->pluck('section')
                            ->filter()
                            ->unique()
                            ->values()
                            ->all())
                        ->all(),
                ];
            })
            ->all();
    }

    private function resizeImage(string $path, int $maxDim): void
    {
        $info = @getimagesize($path);
        if (!$info || ($info[0] <= $maxDim && $info[1] <= $maxDim)) return;

        $ratio = min($maxDim / $info[0], $maxDim / $info[1]);
        $newW  = (int) ($info[0] * $ratio);
        $newH  = (int) ($info[1] * $ratio);

        $src = match ($info[2]) {
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default        => null,
        };
        if (!$src) return;

        $dst = imagecreatetruecolor($newW, $newH);
        if ($info[2] === IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $info[0], $info[1]);

        match ($info[2]) {
            IMAGETYPE_PNG  => imagepng($dst, $path, 7),
            default        => imagejpeg($dst, $path, 85),
        };
        imagedestroy($src);
        imagedestroy($dst);
    }
}
