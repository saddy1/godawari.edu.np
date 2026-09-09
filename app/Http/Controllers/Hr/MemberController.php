<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Card\Department as CardDepartment;
use App\Models\Card\Organization as CardOrganization;
use App\Models\Card\Section as CardSection;
use App\Models\Card\Student;
use App\Models\LibraryLoan;
use App\Models\User;
use App\Models\Hajiri\Department as HajiriDepartment;
use App\Models\Hajiri\Designation;
use App\Models\Hajiri\EmploymentType;
use App\Models\Hajiri\WorkAssigned;
use App\Models\TeachingLearning\AcademicYear;
use App\Services\MemberAccountService;
use App\Services\SubjectEnrollmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use ZipArchive;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $academicYear = AcademicYear::orderByDesc('is_active')->latest('starts_on')->latest('id')->first();
        $query = Student::query()->with([
            'user.roles',
            'academicSection',
            'subjectEnrollments' => fn ($query) => $query
                ->when($academicYear, fn ($query) => $query->where('academic_year', $academicYear->name))
                ->where('assignment_source', 'manual')
                ->whereNotNull('subject_offering_id'),
            'subjectEnrollments.offering',
        ]);

        $query->when($request->filled('type'), fn ($q) => $q->where('member_type', $request->type))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = preg_replace('/\s+/', ' ', trim((string) $request->input('search')));
                $nameTerms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

                $q->where(function ($inner) use ($search, $nameTerms) {
                    $inner->whereRaw(
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
                        ->orWhere('designation', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('stream'), fn ($q) => $q->where('stream', $request->stream))
            ->when($request->filled('section'), fn ($q) => $q->where('section', $request->section))
            ->when($request->filled('permanent_district'), fn ($q) => $q->where('permanent_district', $request->permanent_district))
            ->when($request->filled('permanent_municipality'), fn ($q) => $q->where('permanent_municipality', $request->permanent_municipality));

        $members = $query->latest()->paginate($perPage)->withQueryString();
        $this->attachLibraryClearanceStatus($members->getCollection());

        if ($request->ajax()) {
            return response()->json([
                'html' => view('hr.members._table', compact('members'))->render(),
            ]);
        }

        $counts = [
            'all' => Student::count(),
            'student' => Student::where('member_type', 'student')->count(),
            'teacher' => Student::where('member_type', 'teacher')->count(),
            'staff' => Student::where('member_type', 'staff')->count(),
        ];

        // Teacher/staff users created via Hajiri that have no HR (students) record yet
        $typeFilter = $request->input('type');
        $showOrphans = ! $typeFilter || $typeFilter === 'teacher' || $typeFilter === 'staff';
        $orphanUsers = $showOrphans
            ? User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['teacher', 'staff']))
                ->whereDoesntHave('student')
                ->when($typeFilter, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $typeFilter)))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = $request->input('search');
                    $q->where(function ($inner) use ($search) {
                        $inner->where('name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->get()
            : collect();
        $this->attachLibraryClearanceStatus($orphanUsers, true);

        // Filter options for the UI
        $streams = Student::query()->whereNotNull('stream')->where('stream', '!=', '')->distinct()->orderBy('stream')->pluck('stream');
        $sections = CardSection::query()
            ->when($request->filled('stream'), fn ($q) => $q->whereHas('department', fn ($d) => $d->where('name', $request->stream)))
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();
        $districts = Student::query()->whereNotNull('permanent_district')->where('permanent_district', '!=', '')->distinct()->orderBy('permanent_district')->pluck('permanent_district');
        $municipalities = Student::query()->whereNotNull('permanent_municipality')->where('permanent_municipality', '!=', '')->distinct()->orderBy('permanent_municipality')->pluck('permanent_municipality');

        return view('hr.members.index', compact('members', 'counts', 'orphanUsers', 'streams', 'sections', 'districts', 'municipalities'));
    }

    public function create(Request $request)
    {
        $prefillUser = $this->orphanStaffUserFromRequest($request);

        return view('hr.members.form', [
            'member'      => null,
            'prefillUser' => $prefillUser,
            'formOptions' => $this->buildFormOptions(),
            'hajiriOptions' => $this->hajiriOptions(),
        ]);
    }

    public function store(Request $request, SubjectEnrollmentService $subjectEnrollments)
    {
        $prefillUser = $this->orphanStaffUserFromRequest($request);
        $data = $this->validated($request, null, $prefillUser);
        $password = $request->input('password');
        $loginUserId = $request->input('login_user_id');
        $deviceId = $request->input('device_id');
        $hajiriData = $this->hajiriData($request);
        unset($data['login_user_id'], $data['password'], $data['password_confirmation'], $data['device_id'], $data['designation_id'], $data['employment_type_id'], $data['work_assigned_id'], $data['hajiri_department_id']);

        if (($data['member_type'] ?? null) === 'student') {
            $data['program'] = $data['stream'] ?? null;
        }

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->storePhoto($request, $data['roll_number']);
        } elseif ($request->filled('photo_capture')) {
            $data['photo'] = $this->storeBase64Photo($request->input('photo_capture'), $data['roll_number']);
        }

        DB::transaction(function () use ($data, $password, $loginUserId, $deviceId, $hajiriData, $prefillUser, $subjectEnrollments) {
            $member = Student::create($data);

            if ($prefillUser) {
                $member->forceFill(['user_id' => $prefillUser->id])->save();
            }

            $user = app(MemberAccountService::class)->sync($member, $password, $loginUserId);

            $userData = [
                'device_id' => filled($deviceId) ? (string) $deviceId : $user->device_id,
            ];

            if (in_array($member->member_type, ['teacher', 'staff'], true)) {
                $userData = array_merge($hajiriData, $userData);
            }

            $user->forceFill($userData)->save();
            $subjectEnrollments->syncStudent($member);
        });

        return redirect()->route('admin.hr.members.index')->with('success', 'Member created and synced across ERP modules.');
    }

    public function edit(Student $member)
    {
        return view('hr.members.form', [
            'member'      => $member->load('user'),
            'prefillUser' => null,
            'formOptions' => $this->buildFormOptions(),
            'hajiriOptions' => $this->hajiriOptions(),
            'pendingUpdateRequest' => $member->updateRequests()
                ->where('status', 'pending')
                ->latest()
                ->first(),
        ]);
    }

    public function show(Student $member)
    {
        $member->load([
            'user.roles',
            'user.designation',
            'user.employment',
            'user.working_at',
            'user.hajiriDepartment',
            'academicSection',
            'subjectEnrollments' => fn ($query) => $query->whereNotNull('subject_offering_id'),
            'subjectEnrollments.offering.subject',
        ]);
        $academicYear = AcademicYear::orderByDesc('is_active')->latest('starts_on')->latest('id')->first();
        $electiveEnrollments = $member->subjectEnrollments
            ->when($academicYear, fn ($enrollments) => $enrollments->where('academic_year', $academicYear->name))
            ->filter(fn ($enrollment) => $enrollment->offering?->is_elective)
            ->values();

        return view('hr.members.show', [
            'member' => $member,
            'academicYear' => $academicYear,
            'electiveEnrollments' => $electiveEnrollments,
        ]);
    }

    public function update(Request $request, Student $member, SubjectEnrollmentService $subjectEnrollments)
    {
        if ($member->member_type === 'student') {
            $request->merge([
                'member_type' => $member->member_type,
                'organization' => $member->organization,
                'stream' => $member->stream,
                'section' => $member->section,
                'roll_number' => $member->roll_number,
            ]);
        }

        $data = $this->validated($request, $member);
        $this->preserveOmittedDateFields($request, $member, $data);
        $password = $request->input('password');
        $loginUserId = $request->input('login_user_id');
        $deviceId = $request->input('device_id');
        $hajiriData = $this->hajiriData($request);
        unset($data['login_user_id'], $data['password'], $data['password_confirmation'], $data['device_id'], $data['designation_id'], $data['employment_type_id'], $data['work_assigned_id'], $data['hajiri_department_id']);

        if (($data['member_type'] ?? null) === 'student') {
            $data['program'] = $data['stream'] ?? null;
        }

        if ($request->hasFile('photo')) {
            $this->deletePhoto($member->photo);
            $data['photo'] = $this->storePhoto($request, $data['roll_number']);
        } elseif ($request->filled('photo_capture')) {
            $this->deletePhoto($member->photo);
            $data['photo'] = $this->storeBase64Photo($request->input('photo_capture'), $data['roll_number']);
        }

        try {
            DB::transaction(function () use ($member, $data, $password, $loginUserId, $deviceId, $hajiriData, $subjectEnrollments) {
                $member->update($data);
                $user = app(MemberAccountService::class)->sync($member, $password, $loginUserId);

                $oldDeviceId = $user->device_id;
                $newDeviceId = filled($deviceId) ? (string) $deviceId : null;
                $userData = ['device_id' => $newDeviceId];

                if (in_array($member->member_type, ['teacher', 'staff'], true)) {
                    $userData = array_merge($hajiriData, $userData);
                }

                $user->forceFill($userData)->save();

                if ($oldDeviceId && $newDeviceId && (string) $oldDeviceId !== (string) $newDeviceId) {
                    DB::table('attendacelogs')
                        ->where('user_id', (int) $oldDeviceId)
                        ->update(['user_id' => (int) $newDeviceId]);
                }

                $subjectEnrollments->syncStudent($member->fresh());
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->withInput()->withErrors([
                'email' => 'The email address is already in use by another account.',
            ]);
        }

        return redirect()->route('admin.hr.members.index')->with('success', 'Member updated and synced across ERP modules.');
    }

    private function preserveOmittedDateFields(Request $request, Student $member, array &$data): void
    {
        foreach (['dob', 'dob_bs', 'valid_till', 'valid_till_bs', 'joining_date', 'joining_date_bs', 'permanent_date', 'permanent_date_bs'] as $field) {
            if (! $request->exists($field) && filled($member->{$field})) {
                $data[$field] = $member->{$field};
            }
        }
    }

    public function destroy(Student $member)
    {
        $clearance = $this->libraryClearanceSummary($member->id, $member->user_id);

        if ($clearance['has_hold']) {
            return back()->with('error', "\"{$member->full_name}\" cannot be deleted until library clearance is complete. {$clearance['message']}");
        }

        DB::transaction(function () use ($member) {
            $linkedUser = $member->user;
            $shouldDeleteLinkedStudentLogin = $member->member_type === 'student'
                && $linkedUser
                && $linkedUser->hasRole('student')
                && ! $linkedUser->isSuperAdmin();

            $this->deletePhoto($member->photo);
            $member->delete();

            if ($shouldDeleteLinkedStudentLogin) {
                $linkedUser->delete();
            }
        });

        return back()->with('success', 'Member removed from HR master.');
    }

    public function destroyOrphanUser(User $user)
    {
        if (! $user->hasAnyRole(['teacher', 'staff'])) {
            return back()->with('error', 'Only orphan teacher/staff users can be deleted from this panel.');
        }

        if ($user->student()->exists()) {
            return back()->with('error', "\"{$user->name}\" already has an HR profile. Delete the HR member record instead.");
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Super admin accounts cannot be deleted here.');
        }

        $clearance = $this->libraryClearanceSummary(null, $user->id);
        if ($clearance['has_hold']) {
            return back()->with('error', "\"{$user->name}\" cannot be deleted until library clearance is complete. {$clearance['message']}");
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "User account \"{$name}\" deleted.");
    }

    // ── Bulk edit page: search-and-select members, then update in one go ───
    public function bulkEdit()
    {
        $academicOptions = CardOrganization::query()
            ->with('departments.sections')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (CardOrganization $organization) => [
                $organization->slug => [
                    'label' => $organization->name,
                    'streams' => $organization->departments
                        ->where('is_active', true)
                        ->mapWithKeys(fn ($department) => [
                            $department->name => $department->sections
                                ->where('is_active', true)
                                ->map(fn ($section) => [
                                    'id' => $section->id,
                                    'name' => $section->name,
                                    'group' => $section->group_name,
                                ])
                                ->values()
                                ->all(),
                        ])
                        ->all(),
                ],
            ])
            ->all();

        return view('hr.members.bulk-edit', compact('academicOptions'));
    }

    public function bulkEditSearch(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $type = $request->input('type');
        $organization = $request->input('organization');
        $stream = $request->input('stream');
        $section = $request->input('section');

        if ($q === '' && !$type && !$organization && !$stream && !$section) {
            return response()->json(['members' => []]);
        }

        $members = Student::query()
            ->when($type, fn ($query) => $query->where('member_type', $type))
            ->when($organization, fn ($query) => $query->where('organization', $organization))
            ->when($stream, fn ($query) => $query->where('stream', $stream))
            ->when($section, fn ($query) => $query->where('section', $section))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('first_name', 'like', "%{$q}%")
                        ->orWhere('middle_name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('roll_number', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('mobile', 'like', "%{$q}%");
                });
            })
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'roll_number', 'member_type', 'stream', 'section', 'photo'])
            ->map(fn (Student $s) => [
                'id'          => $s->id,
                'name'        => trim("{$s->first_name} {$s->middle_name} {$s->last_name}"),
                'roll_number' => $s->roll_number,
                'member_type' => $s->member_type,
                'stream'      => $s->stream,
                'section'     => $s->section,
                'photo_url'   => $s->photo_url,
            ]);

        return response()->json(['members' => $members]);
    }

    // ── Bulk update class / section / valid till ────────────────────────────
    public function bulkUpdate(Request $request, SubjectEnrollmentService $subjectEnrollments)
    {
        $request->validate([
            'ids'        => 'required|array|min:1',
            'ids.*'      => 'integer|exists:students,id',
            'valid_till' => 'nullable|date',
            'section_id' => 'nullable|integer|exists:sections,id',
        ]);

        $data = array_filter($request->only(['valid_till']), fn ($value) => filled($value));

        if ($request->filled('section_id')) {
            $containsNonStudents = Student::whereIn('id', $request->ids)
                ->where('member_type', '!=', 'student')
                ->exists();

            if ($containsNonStudents) {
                return back()->with('error', 'A class section can only be assigned to students. Remove teachers or staff from the selection first.');
            }

            $section = CardSection::with('department.organization')->findOrFail($request->integer('section_id'));
            $data['organization'] = $section->department->organization->slug;
            $data['stream'] = $section->department->name;
            $data['section'] = $section->name;
            $data['section_id'] = $section->id;
        }

        if (empty($data)) {
            return back()->with('error', 'Choose at least one field to update before applying to the selected members.');
        }

        $count = Student::whereIn('id', $request->ids)->update($data);

        Student::whereIn('id', $request->ids)
            ->where('member_type', 'student')
            ->with('academicSection')
            ->get()
            ->each(fn (Student $student) => $subjectEnrollments->syncStudent($student));

        return back()->with('success', "{$count} member(s) updated.");
    }

    public function bulkDestroy(Request $request)
    {
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('ids', []))));

        if (empty($ids)) {
            return back()->with('error', 'No members selected.');
        }

        $members = Student::with('user.roles')->whereIn('id', $ids)->get();

        // Block deletion if any selected member has pending library clearance.
        $blocked = $members->filter(fn ($member) => $this
            ->libraryClearanceSummary($member->id, $member->user_id)['has_hold']);

        if ($blocked->isNotEmpty()) {
            $names = $blocked->pluck('full_name')->implode(', ');
            return back()->with('error', "Cannot delete the following members because library clearance is pending: {$names}. Return issued books and settle outstanding fines first.");
        }

        $count = 0;

        DB::transaction(function () use ($members, &$count) {
            foreach ($members as $member) {
                $linkedUser = $member->user;
                $shouldDeleteLinkedStudentLogin = $member->member_type === 'student'
                    && $linkedUser
                    && $linkedUser->hasRole('student')
                    && ! $linkedUser->isSuperAdmin();

                $this->deletePhoto($member->photo);
                $member->delete();

                if ($shouldDeleteLinkedStudentLogin) {
                    $linkedUser->delete();
                }
                $count++;
            }
        });

        return back()->with('success', "{$count} member(s) removed from HR master.");
    }

    /**
     * Add one batched library-clearance status to each member on the HR page.
     * A hold applies to issued/lost books or any unpaid fine. Loans may be
     * linked through either the HR member id or its associated user id.
     */
    private function attachLibraryClearanceStatus(Collection $members, bool $usersOnly = false): void
    {
        if ($members->isEmpty()) {
            return;
        }

        $studentIds = $usersOnly ? collect() : $members->pluck('id')->filter()->map(fn ($id) => (int) $id);
        $userIds = $usersOnly
            ? $members->pluck('id')->filter()->map(fn ($id) => (int) $id)
            : $members->pluck('user_id')->filter()->map(fn ($id) => (int) $id);

        $loans = LibraryLoan::query()
            ->where(function ($query) use ($studentIds, $userIds) {
                if ($studentIds->isNotEmpty()) {
                    $query->whereIn('student_id', $studentIds);
                }
                if ($userIds->isNotEmpty()) {
                    $method = $studentIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('user_id', $userIds);
                }
            })
            ->where(function ($query) {
                $query->whereIn('status', ['issued', 'lost'])
                    ->orWhereRaw('fine_amount > fine_paid');
            })
            ->get(['id', 'student_id', 'user_id', 'status', 'fine_amount', 'fine_paid']);

        foreach ($members as $member) {
            $matching = $loans->filter(function (LibraryLoan $loan) use ($member, $usersOnly) {
                if ($usersOnly) {
                    return (int) $loan->user_id === (int) $member->id;
                }

                return (int) $loan->student_id === (int) $member->id
                    || ($member->user_id && (int) $loan->user_id === (int) $member->user_id);
            });
            $summary = $this->summarizeLibraryClearance($matching);

            $member->setAttribute('library_clearance_hold', $summary['has_hold']);
            $member->setAttribute('library_unreturned_count', $summary['unreturned_count']);
            $member->setAttribute('library_fine_due', $summary['fine_due']);
            $member->setAttribute('library_clearance_message', $summary['message']);
        }
    }

    private function libraryClearanceSummary(?int $studentId, ?int $userId): array
    {
        if (! $studentId && ! $userId) {
            return $this->summarizeLibraryClearance(collect());
        }

        $loans = LibraryLoan::query()
            ->where(function ($query) use ($studentId, $userId) {
                if ($studentId) {
                    $query->where('student_id', $studentId);
                }
                if ($userId) {
                    $method = $studentId ? 'orWhere' : 'where';
                    $query->{$method}('user_id', $userId);
                }
            })
            ->where(function ($query) {
                $query->whereIn('status', ['issued', 'lost'])
                    ->orWhereRaw('fine_amount > fine_paid');
            })
            ->get(['status', 'fine_amount', 'fine_paid']);

        return $this->summarizeLibraryClearance($loans);
    }

    private function summarizeLibraryClearance(Collection $loans): array
    {
        $unreturnedCount = $loans->whereIn('status', ['issued', 'lost'])->count();
        $fineDue = round($loans->sum(fn (LibraryLoan $loan) => max(
            0,
            (float) $loan->fine_amount - (float) $loan->fine_paid
        )), 2);

        $parts = [];
        if ($unreturnedCount > 0) {
            $parts[] = $unreturnedCount.' unreturned '.Str::plural('book', $unreturnedCount);
        }
        if ($fineDue > 0) {
            $parts[] = 'Rs. '.number_format($fineDue, 2).' fine due';
        }

        return [
            'has_hold' => $unreturnedCount > 0 || $fineDue > 0,
            'unreturned_count' => $unreturnedCount,
            'fine_due' => $fineDue,
            'message' => $parts ? implode(' and ', $parts).'.' : 'Library clearance complete.',
        ];
    }

    private function validated(Request $request, ?Student $member = null, ?User $prefillUser = null): array
    {
        if ($request->filled('roll_number') && blank($request->input('login_user_id'))) {
            $request->merge(['login_user_id' => $request->input('roll_number')]);
        }

        // Split full_name → first / middle / last before validation
        if ($request->filled('full_name')) {
            $parts = preg_split('/\s+/', trim($request->input('full_name')), 3);
            $request->merge([
                'first_name'  => $parts[0] ?? '',
                'middle_name' => count($parts) === 3 ? $parts[1] : '',
                'last_name'   => count($parts) >= 2 ? end($parts) : ($parts[0] ?? ''),
            ]);
        }

        $memberType = $request->input('member_type');
        $org        = $request->input('organization');
        $stream     = $request->input('stream') ?: null;
        $section    = $request->input('section') ?: null;
        $ignoredUserId = $member?->user_id ?: $prefillUser?->id;
        $academicSystem = $memberType === 'student'
            ? (CardDepartment::query()
                ->where('name', $stream)
                ->whereHas('organization', fn ($query) => $query->where('slug', $org))
                ->value('academic_system') ?? 'none')
            : 'none';

        $data = $request->validate([
            'organization' => ['required', 'string', 'max:100'],
            'member_type' => ['required', Rule::in(['student', 'teacher', 'staff'])],
            'stream' => ['required_if:member_type,student', 'nullable', 'string', 'max:100'],
            'section' => ['nullable', 'string', 'max:50'],

            // Roll number is unique within (org, member_type, stream, section).
            // A student and a teacher can share the same roll number.
            // Null and empty-string are treated the same for stream/section.
            'roll_number' => [
                'required',
                'string',
                'max:100',
                function ($attr, $value, $fail) use ($org, $memberType, $stream, $section, $member) {
                    $conflict = Student::where('roll_number', $value)
                        ->where('organization', $org)
                        ->where('member_type', $memberType)
                        ->where(function ($q) use ($stream) {
                            $stream
                                ? $q->where('stream', $stream)
                                : $q->where(fn ($q2) => $q2->whereNull('stream')->orWhere('stream', ''));
                        })
                        ->where(function ($q) use ($section) {
                            $section
                                ? $q->where('section', $section)
                                : $q->where(fn ($q2) => $q2->whereNull('section')->orWhere('section', ''));
                        })
                        ->when($member, fn ($q) => $q->where('id', '!=', $member->id))
                        ->first();

                    if ($conflict) {
                        $typeLabel = ucfirst($memberType);
                        $fail("{$typeLabel} ID \"{$value}\" is already taken by {$conflict->full_name}. Please use a different ID.");
                    }
                },
            ],

            // Login user ID (student_code) must be globally unique across all users.
            'login_user_id' => [
                'nullable',
                'string',
                'max:100',
                function ($attr, $value, $fail) use ($ignoredUserId) {
                    if (blank($value)) return;
                    $conflict = User::where('student_code', $value)
                        ->when($ignoredUserId, fn ($q) => $q->where('id', '!=', $ignoredUserId))
                        ->with('student')
                        ->first();
                    if ($conflict) {
                        $name = $conflict->student?->full_name ?? $conflict->name ?? 'another member';
                        $fail("User ID \"{$value}\" is already linked to {$name}. Please choose a different login ID.");
                    }
                },
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_relation' => ['nullable', Rule::in(['father', 'mother', 'grandfather', 'guardian', 'other'])],
            'guardian_contact' => ['nullable', 'string', 'max:30'],
            'father_name' => ['nullable', 'string', 'max:150'],
            'mother_name' => ['nullable', 'string', 'max:150'],
            'grandfather_name' => ['nullable', 'string', 'max:150'],
            'registration_no' => ['nullable', 'string', 'max:100'],
            'dob' => ['nullable', 'date'],
            'dob_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'gender' => ['nullable', 'string', 'max:30'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'citizenship_no' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'parent_contact' => ['nullable', 'string', 'max:30'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'designation' => ['nullable', 'string', 'max:100'],
            'employment_type' => ['nullable', 'string', 'max:100'],
            'employee_category' => ['nullable', Rule::in(['academic', 'administrative'])],
            'joining_date' => ['nullable', 'date'],
            'joining_date_bs' => ['nullable', 'string', 'max:20'],
            'permanent_date' => ['nullable', 'date'],
            'permanent_date_bs' => ['nullable', 'string', 'max:20'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'bank_branch' => ['nullable', 'string', 'max:150'],
            'bank_account_name' => ['nullable', 'string', 'max:150'],
            'bank_account_number' => ['nullable', 'string', 'max:80'],
            'pan_number' => ['nullable', 'string', 'max:80'],
            'ssf_number' => ['nullable', 'string', 'max:80'],
            'cit_number' => ['nullable', 'string', 'max:80'],
            'valid_till' => ['nullable', 'date'],
            'valid_till_bs' => ['nullable', 'string', 'max:20'],
            'program' => ['nullable', 'string', 'max:100'],
            'batch' => ['nullable', 'string', 'max:20'],
            'semester' => [Rule::requiredIf($academicSystem === 'semester'), 'nullable', 'integer', 'min:1', 'max:8'],
            'year_level' => [Rule::requiredIf($academicSystem === 'year'), 'nullable', 'integer', 'min:1', 'max:6'],
            'zone' => ['nullable', 'string', 'max:50'],
            'district' => ['nullable', 'string', 'max:50'],
            'municipality' => ['nullable', 'string', 'max:100'],
            'permanent_province' => ['nullable', 'string', 'max:100'],
            'permanent_district' => ['nullable', 'string', 'max:100'],
            'permanent_municipality' => ['nullable', 'string', 'max:150'],
            'permanent_ward' => ['nullable', 'string', 'max:20'],
            'permanent_tole' => ['nullable', 'string', 'max:150'],
            'address_en' => ['nullable', 'string', 'max:300'],
            'temporary_province' => ['nullable', 'string', 'max:100'],
            'temporary_district' => ['nullable', 'string', 'max:100'],
            'temporary_municipality' => ['nullable', 'string', 'max:150'],
            'temporary_ward' => ['nullable', 'string', 'max:20'],
            'temporary_tole' => ['nullable', 'string', 'max:150'],
            'bus_route' => ['nullable', 'string', 'max:100'],
            'bus_stop' => ['nullable', 'string', 'max:100'],
            'has_bus_pass' => ['boolean'],
            'library_id' => ['nullable', 'string', 'max:50'],
            'has_library_card' => ['boolean'],
            'password' => [$member ? 'nullable' : 'nullable', 'confirmed', Password::min(8)],
            'device_id' => [
                'nullable',
                'integer',
                'min:1',
                function ($attr, $value, $fail) use ($ignoredUserId) {
                    if (blank($value)) return;
                    $conflict = User::where('device_id', (int) $value)
                        ->when($ignoredUserId, fn ($q) => $q->where('id', '!=', $ignoredUserId))
                        ->with('student')
                        ->first();
                    if ($conflict) {
                        $name = $conflict->student?->full_name ?? $conflict->name ?? 'another member';
                        $fail("Device ID {$value} is already assigned to {$name}. Each device can only be linked to one member.");
                    }
                },
            ],
            'designation_id' => $this->optionalForeignIdRules((new Designation())->getTable()),
            'employment_type_id' => $this->optionalForeignIdRules((new EmploymentType())->getTable()),
            'work_assigned_id' => $this->optionalForeignIdRules((new WorkAssigned())->getTable()),
            'hajiri_department_id' => $this->optionalForeignIdRules((new HajiriDepartment())->getTable()),
        ]);

        if ($memberType === 'student') {
            if ($academicSystem === 'semester') {
                $data['year_level'] = null;
            } elseif ($academicSystem === 'year') {
                $data['semester'] = null;
            } else {
                $data['semester'] = null;
                $data['year_level'] = null;
            }
        } else {
            $data['semester'] = null;
            $data['year_level'] = null;
        }

        return $data;
    }

    private function orphanStaffUserFromRequest(Request $request): ?User
    {
        if (! $request->filled('prefill_user')) {
            return null;
        }

        return User::with('roles')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['teacher', 'staff']))
            ->whereDoesntHave('student')
            ->find((int) $request->input('prefill_user'));
    }

    // Maps normalised IEMIS/Excel column names to internal field names.
    private array $xlsxColumnMap = [
        'sn'                      => 'roll_number',
        's_n'                     => 'roll_number',
        'roll_number'             => 'roll_number',
        'student_id'              => 'registration_no',
        'registration_no'         => 'registration_no',
        'fullname'                => '_fullname',
        'full_name'               => '_fullname',
        'first_name'              => 'first_name',
        'middle_name'             => 'middle_name',
        'last_name'               => 'last_name',
        'gender'                  => 'gender',
        'father_name'             => 'father_name',
        'mother_name'             => 'mother_name',
        'grandfather_name'        => 'grandfather_name',
        'dob'                     => 'dob_bs',
        'dob_bs'                  => 'dob_bs',
        'permanent_address'       => 'address_en',
        'address_en'              => 'address_en',
        'guardian_name'           => 'guardian_name',
        'guardian_contact_number' => 'guardian_contact',
        'guardian_contact'        => 'guardian_contact',
        'mobile'                  => 'mobile',
        'email'                   => 'email',
        'citizenship_no'          => 'citizenship_no',
        'year'                    => 'batch',
        'batch'                   => 'batch',
        'program'                 => 'program',
        'stream'                  => 'stream',
        'section'                 => 'section',
        'member_type'             => 'member_type',
        'designation'             => 'designation',
        'employment_type'         => 'employment_type',
        'blood_group'             => 'blood_group',
        'parent_contact'          => 'parent_contact',
        'joining_date'            => 'joining_date',
        'permanent_date'          => 'permanent_date',
        'bank_name'               => 'bank_name',
        'bank_branch'             => 'bank_branch',
        'bank_account_name'       => 'bank_account_name',
        'bank_account_number'     => 'bank_account_number',
        'pan_number'              => 'pan_number',
        'device_id'               => 'device_id',
        'password'                => 'password',
        'login_user_id'           => 'login_user_id',
    ];

    public function importForm()
    {
        return view('hr.members.import', [
            'formOptions'   => $this->buildFormOptions(),
            'hajiriOptions' => $this->hajiriOptions(),
        ]);
    }

    // ── CSV Preview ───────────────────────────────────────────────────────
    public function importCsvPreview(Request $request)
    {
        $request->validate([
            'csv_file'           => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'organization'       => ['required', 'string', 'max:100'],
            'member_type'        => ['required', Rule::in(['student', 'teacher', 'staff'])],
            'stream'             => ['required', 'string', 'max:100'],
            'section'            => ['required', 'string', 'max:50'],
            'employee_category'  => ['nullable', Rule::in(['academic', 'administrative'])],
            'designation_id'     => $this->optionalForeignIdRules((new Designation())->getTable()),
            'employment_type_id' => $this->optionalForeignIdRules((new EmploymentType())->getTable()),
            'work_assigned_id'   => $this->optionalForeignIdRules((new WorkAssigned())->getTable()),
            'hajiri_department_id' => $this->optionalForeignIdRules((new HajiriDepartment())->getTable()),
        ]);

        $handle  = fopen($request->file('csv_file')->getRealPath(), 'r');
        $headers = array_map(fn($h) => strtolower(str_replace([' ', '-'], '_', trim($h))), fgetcsv($handle) ?: []);

        $required = ['first_name', 'last_name', 'roll_number'];
        $missing  = array_diff($required, $headers);
        if ($missing) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'CSV is missing columns: ' . implode(', ', $missing)]);
        }

        $rows   = [];
        $lineNo = 1;
        while (($raw = fgetcsv($handle)) !== false) {
            $lineNo++;
            if (count($raw) !== count($headers)) continue;
            $data = array_combine($headers, array_map('trim', $raw));
            if (empty(array_filter($data))) continue;
            $rows[] = $this->buildImportRow($lineNo, $data, $request->organization, $request->stream, $request->section);
        }
        fclose($handle);

        if (empty($rows)) {
            return back()->withErrors(['csv_file' => 'No data rows found.']);
        }

        return $this->showImportPreview($rows, $request);
    }

    // ── Excel / IEMIS Preview ─────────────────────────────────────────────
    public function importXlsxPreview(Request $request)
    {
        $request->validate([
            'xlsx_file'          => ['required', 'file', 'mimes:xlsx,xls,ods,csv,txt', 'max:10240'],
            'organization'       => ['required', 'string', 'max:100'],
            'member_type'        => ['required', Rule::in(['student', 'teacher', 'staff'])],
            'stream'             => ['required', 'string', 'max:100'],
            'section'            => ['required', 'string', 'max:50'],
            'employee_category'  => ['nullable', Rule::in(['academic', 'administrative'])],
            'designation_id'     => $this->optionalForeignIdRules((new Designation())->getTable()),
            'employment_type_id' => $this->optionalForeignIdRules((new EmploymentType())->getTable()),
            'work_assigned_id'   => $this->optionalForeignIdRules((new WorkAssigned())->getTable()),
            'hajiri_department_id' => $this->optionalForeignIdRules((new HajiriDepartment())->getTable()),
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('xlsx_file')->getRealPath());
        } catch (\Throwable $e) {
            return back()->withErrors(['xlsx_file' => 'Could not read file: ' . $e->getMessage()]);
        }

        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        if (empty($sheetData)) {
            return back()->withErrors(['xlsx_file' => 'The file is empty.']);
        }

        $rawHeaders = array_shift($sheetData);
        $normalized = array_map(function ($h) {
            $h = strtolower(trim((string) $h));
            $h = str_replace([' ', '-'], '_', $h);
            $h = preg_replace('/[^a-z0-9_]/', '_', $h);
            return preg_replace('/_+/', '_', trim($h, '_'));
        }, $rawHeaders);

        // Build column index → internal field name map
        $fieldMap     = [];
        $hasRollCol   = false;
        foreach ($normalized as $i => $h) {
            $field = $this->xlsxColumnMap[$h] ?? null;
            $fieldMap[$i] = $field;
            if ($field === 'roll_number') $hasRollCol = true;
        }

        $rows     = [];
        $lineNo   = 1;
        $autoRoll = 0;

        foreach ($sheetData as $rawRow) {
            $lineNo++;
            if (empty(array_filter(array_map('strval', $rawRow)))) continue;

            $data = [];
            foreach ($rawRow as $i => $val) {
                $field = $fieldMap[$i] ?? null;
                if (!$field) continue;
                $val = trim((string) $val);

                if ($field === '_fullname') {
                    $parts = preg_split('/\s+/', $val, 3);
                    $data['first_name']  = ucfirst(strtolower($parts[0] ?? ''));
                    $data['last_name']   = ucfirst(strtolower(end($parts)));
                    $data['middle_name'] = count($parts) === 3 ? ucwords(strtolower($parts[1])) : '';
                } else {
                    $data[$field] = $val;
                }
            }

            if (!$hasRollCol || empty($data['roll_number'])) {
                $autoRoll++;
                $data['roll_number'] = (string) $autoRoll;
            }

            $rows[] = $this->buildImportRow($lineNo, $data, $request->organization, $request->stream, $request->section);
        }

        if (empty($rows)) {
            return back()->withErrors(['xlsx_file' => 'No data rows found in the file.']);
        }

        return $this->showImportPreview($rows, $request);
    }

    // ── School Class List Preview ─────────────────────────────────────────
    // Supports class exports that contain school details above the headings.
    // In this format "Admission No" is the HR/Card roll_number.
    public function importClassListPreview(Request $request)
    {
        $request->validate([
            'class_list_file' => ['required', 'file', 'mimes:xlsx,xls,ods,csv,txt', 'max:10240'],
            'organization'    => ['required', 'string', 'max:100'],
            'stream'          => ['required', 'string', 'max:100'],
            'section'         => ['required', 'string', 'max:50'],
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('class_list_file')->getRealPath());
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'class_list_file' => 'Could not read the class-list file: '.$e->getMessage(),
            ]);
        }

        if (empty($sheetData)) {
            return back()->withInput()->withErrors(['class_list_file' => 'The class-list file is empty.']);
        }

        $headerIndex = null;
        $normalizedHeaders = [];

        // Search rather than assuming row 1. School exports commonly put their
        // name, address, PAN and contact details before the actual table.
        foreach (array_slice($sheetData, 0, 50, true) as $index => $candidate) {
            $headers = array_map(fn ($value) => $this->normalizeSpreadsheetHeader($value), $candidate);
            if (in_array('name', $headers, true)
                && (in_array('admission_no', $headers, true) || in_array('admission_number', $headers, true))) {
                $headerIndex = $index;
                $normalizedHeaders = $headers;
                break;
            }
        }

        if ($headerIndex === null) {
            return back()->withInput()->withErrors([
                'class_list_file' => 'Could not find the heading row. The sheet must contain “Name” and “Admission No” columns (headings may appear after school-information rows).',
            ]);
        }

        $columnAliases = [
            'sn'               => '_serial',
            's_n'              => '_serial',
            'serial_no'        => '_serial',
            'name'             => '_fullname',
            'student_name'     => '_fullname',
            'full_name'        => '_fullname',
            'admission_no'     => 'roll_number',
            'admission_number' => 'roll_number',
            'roll_no'          => '_source_roll',
            'roll_number'      => '_source_roll',
            'date_of_birth_bs' => 'dob_bs',
            'birth_date_bs'    => 'dob_bs',
            'dob_bs'           => 'dob_bs',
            'date_of_birth_ad' => 'dob',
            'birth_date_ad'    => 'dob',
            'dob_ad'           => 'dob',
            'gender'           => 'gender',
            'sex'              => 'gender',
            'house'            => '_house',
            'fee_category'     => '_fee_category',
            'student_category' => '_student_category',
            'contact_no'       => 'mobile',
            'contact_number'   => 'mobile',
            'mobile'           => 'mobile',
            'mobile_no'        => 'mobile',
        ];

        $fieldMap = [];
        foreach ($normalizedHeaders as $column => $heading) {
            $fieldMap[$column] = $columnAliases[$heading] ?? null;
        }

        $rows = [];
        $seenAdmissions = [];
        $generatedLoginIds = [];
        foreach (array_slice($sheetData, $headerIndex + 1, null, true) as $rowIndex => $rawRow) {
            if (empty(array_filter($rawRow, fn ($value) => trim((string) $value) !== ''))) {
                continue;
            }

            $data = [];
            foreach ($rawRow as $column => $value) {
                $field = $fieldMap[$column] ?? null;
                if ($field) {
                    $data[$field] = trim((string) $value);
                }
            }

            // Ignore footer/summary rows that have neither a name nor admission number.
            if (blank($data['_fullname'] ?? null) && blank($data['roll_number'] ?? null)) {
                continue;
            }

            [$firstName, $middleName, $lastName] = $this->splitImportName($data['_fullname'] ?? '');
            $data['first_name'] = $firstName;
            $data['middle_name'] = $middleName;
            $data['last_name'] = $lastName;
            $data['gender'] = $this->normalizeImportGender($data['gender'] ?? '');
            do {
                $data['login_user_id'] = $this->generateLoginIdCandidate($request->organization);
            } while (isset($generatedLoginIds[$data['login_user_id']]));
            $generatedLoginIds[$data['login_user_id']] = true;

            $sourceDob = trim($data['dob'] ?? '');
            $sourceDobBs = trim($data['dob_bs'] ?? '');
            $normalizedDob = $this->normalizeDate($sourceDob);
            $normalizedDobBs = $this->normalizeBsDate($sourceDobBs);
            $data['dob'] = $normalizedDob ?? '';
            $data['dob_bs'] = $normalizedDobBs ?? '';

            $lineNumber = $rowIndex + 1;
            $row = $this->buildImportRow(
                $lineNumber,
                $data,
                $request->organization,
                $request->stream,
                $request->section,
                false
            );

            $problems = $row['error'] ? [$row['error']] : [];
            $admissionNo = trim($row['roll_number']);

            if ($sourceDob !== '' && $normalizedDob === null) {
                $problems[] = "Invalid DOB (AD) “{$sourceDob}”; use YYYY-MM-DD";
            }
            if ($sourceDobBs !== '' && $normalizedDobBs === null) {
                $problems[] = "Invalid DOB (BS) “{$sourceDobBs}”; use YYYY-MM-DD";
            }
            if ($admissionNo !== '' && isset($seenAdmissions[strtolower($admissionNo)])) {
                $problems[] = "Duplicate Admission No; first appears on row {$seenAdmissions[strtolower($admissionNo)]}";
            } elseif ($admissionNo !== '') {
                $seenAdmissions[strtolower($admissionNo)] = $lineNumber;
            }

            $row['source_roll_number'] = $data['_source_roll'] ?? '';
            $row['house'] = $data['_house'] ?? '';
            $row['fee_category'] = $data['_fee_category'] ?? '';
            $row['student_category'] = $data['_student_category'] ?? '';
            $row['error'] = $problems ? implode(' • ', array_unique($problems)) : null;
            $rows[] = $row;
        }

        if (empty($rows)) {
            return back()->withInput()->withErrors([
                'class_list_file' => 'The headings were found, but no student rows were found below them.',
            ]);
        }

        // Two batched lookups replace three database queries for every row.
        $admissionNumbers = collect($rows)->pluck('roll_number')->filter()->unique()->values();
        $loginIds = collect($rows)->pluck('login_user_id')->filter()->unique()->values();
        $existingAdmissions = Student::query()
            ->where('organization', $request->organization)
            ->where('member_type', 'student')
            ->where('stream', $request->stream)
            ->where('section', $request->section)
            ->whereIn('roll_number', $admissionNumbers)
            ->pluck('roll_number')
            ->mapWithKeys(fn ($roll) => [strtolower((string) $roll) => true]);
        $existingLoginIds = User::query()
            ->whereIn('student_code', $loginIds)
            ->pluck('student_code')
            ->mapWithKeys(fn ($loginId) => [strtolower((string) $loginId) => true]);

        foreach ($rows as &$row) {
            $problems = $row['error'] ? explode(' • ', $row['error']) : [];
            if (isset($existingAdmissions[strtolower((string) $row['roll_number'])])) {
                $problems[] = "Admission No {$row['roll_number']} already exists in the selected class and section";
            }
            if (isset($existingLoginIds[strtolower((string) $row['login_user_id'])])) {
                $problems[] = "Generated Login ID {$row['login_user_id']} already exists; preview the file again";
            }
            $row['error'] = $problems ? implode(' • ', array_unique($problems)) : null;
        }
        unset($row);

        $request->merge([
            'member_type' => 'student',
            'employee_category' => null,
            'designation_id' => null,
            'employment_type_id' => null,
            'work_assigned_id' => null,
            'hajiri_department_id' => null,
            'import_kind' => 'class_list',
        ]);

        return $this->showImportPreview($rows, $request);
    }

    // ── Confirm Import (shared by CSV and Excel) ──────────────────────────
    public function confirmImport(Request $request)
    {
        $rows    = session('hr_import_rows', []);
        $context = session('hr_import_context');

        if (!$rows || !$context) {
            return redirect()->route('admin.hr.members.import')
                ->withErrors(['error' => 'Session expired. Please re-upload.']);
        }

        $valid   = array_filter($rows, fn($r) => !$r['error']);
        $created = 0;

        if (($context['import_kind'] ?? null) === 'class_list') {
            $created = $this->confirmClassListImport($valid, $context);
            session()->forget(['hr_import_rows', 'hr_import_context']);

            return redirect()->route('admin.hr.members.index')
                ->with('success', "{$created} students imported and synced.");
        }

        DB::transaction(function () use ($valid, $context, &$created) {
            foreach ($valid as $row) {
                $rowOrg     = $row['organization']  ?? $context['organization'];
                $rowStream  = ($row['stream']  ?? '') ?: $context['stream'];
                $rowSection = ($row['section'] ?? '') ?: $context['section'];

                $loginUserId = trim($row['login_user_id'] ?? '')
                    ?: $this->generateLoginId($rowOrg, $rowStream, $rowSection, $row['roll_number']);

                if (Student::where('roll_number', $row['roll_number'])
                        ->where('organization', $rowOrg)
                        ->when($rowStream,  fn ($q) => $q->where('stream',  $rowStream))
                        ->when($rowSection, fn ($q) => $q->where('section', $rowSection))
                        ->exists()
                    || User::where('student_code', $loginUserId)->exists()) {
                    continue;
                }

                $memberType = in_array($row['member_type'] ?? '', ['student', 'teacher', 'staff'], true)
                    ? $row['member_type']
                    : $context['member_type'];

                $dobAd = $row['dob'] ?: ($row['dob_bs'] ? $this->bsToAdDate($row['dob_bs']) : null);

                $member = Student::create([
                    'organization'        => $row['organization'] ?? $context['organization'],
                    'member_type'         => $memberType,
                    'stream'              => ($row['stream'] ?? '') ?: $context['stream'],
                    'section'             => ($row['section'] ?? '') ?: $context['section'],
                    'roll_number'         => $row['roll_number'],
                    'registration_no'     => $row['registration_no']     ?: null,
                    'first_name'          => $row['first_name'],
                    'middle_name'         => $row['middle_name']          ?: null,
                    'last_name'           => $row['last_name'],
                    'gender'              => $row['gender']               ?: null,
                    'dob'                 => $dobAd,
                    'dob_bs'              => $row['dob_bs']               ?: null,
                    'mobile'              => $row['mobile']               ?: null,
                    'email'               => $row['email']                ?: null,
                    'father_name'         => $row['father_name']          ?: null,
                    'mother_name'         => $row['mother_name']          ?: null,
                    'grandfather_name'    => $row['grandfather_name']     ?: null,
                    'guardian_name'       => $row['guardian_name']        ?: null,
                    'guardian_contact'    => $row['guardian_contact']     ?: null,
                    'parent_contact'      => $row['parent_contact']       ?: null,
                    'address_en'          => $row['address_en']           ?: null,
                    'citizenship_no'      => $row['citizenship_no']       ?: null,
                    'blood_group'         => $row['blood_group']          ?: null,
                    'designation'         => $row['designation']          ?: null,
                    'employment_type'     => $row['employment_type']      ?: null,
                    'employee_category'   => $context['employee_category'] ?: null,
                    'joining_date'        => blank($row['joining_date'] ?? null) ? null : $row['joining_date'],
                    'permanent_date'      => blank($row['permanent_date'] ?? null) ? null : $row['permanent_date'],
                    'bank_name'           => $row['bank_name']            ?: null,
                    'bank_branch'         => $row['bank_branch']          ?: null,
                    'bank_account_name'   => $row['bank_account_name']   ?: null,
                    'bank_account_number' => $row['bank_account_number'] ?: null,
                    'pan_number'          => $row['pan_number']           ?: null,
                    'program'             => $memberType === 'student'
                        ? (($row['stream'] ?? '') ?: $context['stream'])
                        : ($row['program'] ?? null),
                    'has_bus_pass'        => false,
                    'has_library_card'    => false,
                ]);

                $user = app(MemberAccountService::class)->sync($member, $row['password'] ?: null, $loginUserId);

                $importedUserData = [
                    'device_id' => $row['device_id'] ?: null,
                ];

                if (in_array($memberType, ['teacher', 'staff'], true)) {
                    $importedUserData = array_merge($importedUserData, [
                        'designation_id'       => $context['designation_id']   ?: null,
                        'employment_type_id'   => $context['employment_type_id'] ?: null,
                        'work_assigned_id'     => $context['work_assigned_id'] ?: null,
                        'hajiri_department_id' => $context['hajiri_department_id'] ?: null,
                    ]);
                }

                $user->forceFill($importedUserData)->save();

                $created++;
            }
        });

        session()->forget(['hr_import_rows', 'hr_import_context']);

        return redirect()->route('admin.hr.members.index')
            ->with('success', "{$created} members imported and synced.");
    }

    /**
     * Fast path for class-list uploads.
     *
     * It performs duplicate checks, user creation, student creation and role
     * assignment in batches instead of invoking the account synchronizer (and
     * its many database queries) once for every student.
     */
    private function confirmClassListImport(array $rows, array $context): int
    {
        if (empty($rows)) {
            return 0;
        }

        @set_time_limit(300);

        $organizationSlug = (string) $context['organization'];
        $stream = (string) $context['stream'];
        $section = (string) $context['section'];
        $rollNumbers = collect($rows)->pluck('roll_number')->filter()->unique()->values();
        $loginIds = collect($rows)->pluck('login_user_id')->filter()->unique()->values();

        $existingRolls = Student::query()
            ->where('organization', $organizationSlug)
            ->where('member_type', 'student')
            ->where('stream', $stream)
            ->where('section', $section)
            ->whereIn('roll_number', $rollNumbers)
            ->pluck('roll_number')
            ->mapWithKeys(fn ($roll) => [strtolower((string) $roll) => true]);
        $existingLogins = User::query()
            ->whereIn('student_code', $loginIds)
            ->pluck('student_code')
            ->mapWithKeys(fn ($loginId) => [strtolower((string) $loginId) => true]);

        $rows = array_values(array_filter($rows, fn ($row) => ! isset(
            $existingRolls[strtolower((string) $row['roll_number'])]
        ) && ! isset(
            $existingLogins[strtolower((string) $row['login_user_id'])]
        )));

        if (empty($rows)) {
            return 0;
        }

        $organization = CardOrganization::query()->where('slug', $organizationSlug)->first();
        $department = $organization
            ? CardDepartment::query()
                ->where('organization_id', $organization->id)
                ->where('name', $stream)
                ->first()
            : null;
        $studentRole = Role::findOrCreate('student', 'web');
        $now = now();
        // Imported accounts remain unusable until a real password is generated.
        // Reusing one discarded random hash avoids one expensive bcrypt call per row.
        $provisionedPasswordHash = Hash::make(Str::random(64));

        DB::transaction(function () use (
            $rows,
            $organizationSlug,
            $stream,
            $section,
            $organization,
            $department,
            $studentRole,
            $now,
            $provisionedPasswordHash
        ) {
            $userRows = [];
            foreach ($rows as $row) {
                $loginId = (string) $row['login_user_id'];
                $userRows[] = [
                    'name' => trim("{$row['first_name']} {$row['middle_name']} {$row['last_name']}"),
                    'email' => strtolower($loginId).'@student.local',
                    'password' => $provisionedPasswordHash,
                    'student_code' => $loginId,
                    'class_grade' => $stream,
                    'section' => $section,
                    'organization_id' => $organization?->id,
                    'department_id' => $department?->id,
                    'phone' => $row['mobile'] ?: null,
                    'is_active' => true,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($userRows, 200) as $chunk) {
                DB::table('users')->insert($chunk);
            }

            $usersByLogin = User::query()
                ->whereIn('student_code', collect($rows)->pluck('login_user_id'))
                ->get(['id', 'student_code'])
                ->keyBy('student_code');

            $studentRows = [];
            $roleRows = [];
            foreach ($rows as $row) {
                $user = $usersByLogin->get($row['login_user_id']);
                if (! $user) {
                    continue;
                }

                $dobAd = $row['dob'] ?: ($row['dob_bs'] ? $this->bsToAdDate($row['dob_bs']) : null);
                $studentRows[] = [
                    'user_id' => $user->id,
                    'organization' => $organizationSlug,
                    'member_type' => 'student',
                    'stream' => $stream,
                    'section' => $section,
                    'program' => $stream,
                    'roll_number' => $row['roll_number'],
                    'registration_no' => $row['registration_no'] ?: null,
                    'first_name' => $row['first_name'],
                    'middle_name' => $row['middle_name'] ?: null,
                    'last_name' => $row['last_name'],
                    'gender' => $row['gender'] ?: null,
                    'dob' => $dobAd,
                    'dob_bs' => $row['dob_bs'] ?: null,
                    'mobile' => $row['mobile'] ?: null,
                    'email' => $row['email'] ?: null,
                    'father_name' => $row['father_name'] ?: null,
                    'mother_name' => $row['mother_name'] ?: null,
                    'grandfather_name' => $row['grandfather_name'] ?: null,
                    'guardian_name' => $row['guardian_name'] ?: null,
                    'guardian_contact' => $row['guardian_contact'] ?: null,
                    'parent_contact' => $row['parent_contact'] ?: null,
                    'address_en' => $row['address_en'] ?: null,
                    'citizenship_no' => $row['citizenship_no'] ?: null,
                    'blood_group' => $row['blood_group'] ?: null,
                    'has_bus_pass' => false,
                    'has_library_card' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $roleRows[] = [
                    'role_id' => $studentRole->id,
                    'model_type' => User::class,
                    'model_id' => $user->id,
                ];
            }

            foreach (array_chunk($studentRows, 200) as $chunk) {
                DB::table('students')->insert($chunk);
            }
            foreach (array_chunk($roleRows, 500) as $chunk) {
                DB::table('model_has_roles')->insertOrIgnore($chunk);
            }
        });

        return count($rows);
    }

    // ── Photo ZIP Preview ─────────────────────────────────────────────────
    public function importPhotosPreview(Request $request)
    {
        $request->validate([
            'zip_file'     => ['required', 'file', 'mimes:zip', 'max:102400'],
            'organization' => ['required', 'string', 'max:100'],
            'stream'       => ['nullable', 'string', 'max:100'],
            'section'      => ['nullable', 'string', 'max:50'],
        ]);

        $org     = $request->organization;
        $stream  = $request->stream ?: null;
        $section = $request->section ?: null;

        $students = Student::where('organization', $org)
            ->when($stream,  fn($q) => $q->where('stream', $stream))
            ->when($section, fn($q) => $q->where('section', $section))
            ->get()->keyBy('roll_number');

        if ($students->isEmpty()) {
            return back()->withErrors(['zip_file' => 'No members found for the selected filters.']);
        }

        $uuid    = Str::uuid()->toString();
        $tempDir = public_path("temp_photos/{$uuid}");
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

        $zip     = new ZipArchive();
        if ($zip->open($request->file('zip_file')->getRealPath()) !== true) {
            return back()->withErrors(['zip_file' => 'Could not open ZIP file.']);
        }

        $imageExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $photoRows = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with($name, '/') || str_starts_with(basename($name), '.')) continue;
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $imageExts)) continue;

            $roll = pathinfo(basename($name), PATHINFO_FILENAME);
            file_put_contents("{$tempDir}/{$roll}.{$ext}", $zip->getFromIndex($i));

            $student     = $students->get($roll);
            $photoRows[] = [
                'roll'          => $roll,
                'filename'      => basename($name),
                'ext'           => $ext,
                'temp_url'      => asset("temp_photos/{$uuid}/{$roll}.{$ext}"),
                'student_id'    => $student?->id,
                'student_name'  => $student?->full_name,
                'current_photo' => $student?->photo_url,
                'has_photo'     => $student && !empty($student->photo),
                'action'        => $student ? ($student->photo ? 'replace' : 'add') : 'no_match',
            ];
        }
        $zip->close();

        usort($photoRows, fn($a, $b) => strcmp(
            ['add' => 0, 'replace' => 1, 'no_match' => 2][$a['action']],
            ['add' => 0, 'replace' => 1, 'no_match' => 2][$b['action']]
        ));

        $photoContext = compact('org', 'stream', 'section', 'uuid');
        session(['hr_photo_rows' => $photoRows, 'hr_photo_context' => $photoContext]);

        return view('hr.members.import', [
            'photoRows'     => $photoRows,
            'photoContext'  => $photoContext,
            'formOptions'   => $this->buildFormOptions(),
            'hajiriOptions' => $this->hajiriOptions(),
        ]);
    }

    // ── Photo ZIP Confirm ─────────────────────────────────────────────────
    public function importPhotosConfirm(Request $request)
    {
        $photoRows    = session('hr_photo_rows', []);
        $photoContext = session('hr_photo_context');

        if (!$photoRows || !$photoContext) {
            return redirect()->route('admin.hr.members.import')
                ->withErrors(['error' => 'Session expired. Please re-upload the ZIP.']);
        }

        $uuid    = $photoContext['uuid'];
        $tempDir = public_path("temp_photos/{$uuid}");
        $destDir = public_path('photos');
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);

        $skip     = array_flip($request->input('skip', []));
        $added    = 0;
        $replaced = 0;

        foreach ($photoRows as $row) {
            if ($row['action'] === 'no_match') continue;
            if (isset($skip[$row['roll']])) continue;

            $tempFile = "{$tempDir}/{$row['roll']}.{$row['ext']}";
            if (!file_exists($tempFile)) continue;

            $destFile = "{$destDir}/{$row['roll']}.{$row['ext']}";
            copy($tempFile, $destFile);
            Student::where('id', $row['student_id'])->update(['photo' => "photos/{$row['roll']}.{$row['ext']}"]);
            $row['action'] === 'replace' ? $replaced++ : $added++;
        }

        if (is_dir($tempDir)) File::deleteDirectory($tempDir);
        session()->forget(['hr_photo_rows', 'hr_photo_context']);

        return redirect()->route('admin.hr.members.index')
            ->with('success', "{$added} photo(s) added, {$replaced} replaced.");
    }

    // ── Shared helpers ────────────────────────────────────────────────────
    private function buildImportRow(int $lineNo, array $data, string $defaultOrg, ?string $defaultStream, ?string $defaultSection): array
    {
        $dob    = $this->normalizeDate($data['dob'] ?? '');
        $dobBs  = $data['dob_bs'] ?? '';
        $mobile = ($data['mobile'] ?? '') ?: ($data['guardian_contact'] ?? '');

        $rollNumber = $data['roll_number'] ?? '';

        $effectiveStream  = ($data['stream']  ?? '') ?: ($defaultStream  ?? '');
        $effectiveSection = ($data['section'] ?? '') ?: ($defaultSection ?? '');

        $loginUserId = trim($data['login_user_id'] ?? '')
            ?: $this->generateLoginId($defaultOrg, $effectiveStream, $effectiveSection, $rollNumber);

        $rollExists = $rollNumber && Student::where('roll_number', $rollNumber)
            ->where('organization', $defaultOrg)
            ->when($effectiveStream,  fn ($q) => $q->where('stream',  $effectiveStream))
            ->when($effectiveSection, fn ($q) => $q->where('section', $effectiveSection))
            ->exists();
        $loginExists = $loginUserId && User::where('student_code', $loginUserId)->exists();

        $error = null;
        if (empty($rollNumber))          $error = 'Roll number is empty';
        elseif (empty($data['first_name'])) $error = 'First name is empty';
        elseif (empty($data['last_name']))  $error = 'Last name is empty';
        elseif ($rollExists)             $error = "Roll #{$rollNumber} already exists";
        elseif ($loginExists)            $error = "Login ID '{$loginUserId}' already taken";

        return [
            'line'             => $lineNo,
            'roll_number'      => $rollNumber,
            'login_user_id'    => $loginUserId,
            'first_name'       => $data['first_name']       ?? '',
            'middle_name'      => $data['middle_name']      ?? '',
            'last_name'        => $data['last_name']        ?? '',
            'gender'           => $data['gender']           ?? '',
            'dob'              => $dob ?? ($data['dob']     ?? ''),
            'dob_bs'           => $dobBs,
            'mobile'           => $mobile,
            'email'            => $data['email']            ?? '',
            'father_name'      => $data['father_name']      ?? '',
            'mother_name'      => $data['mother_name']      ?? '',
            'grandfather_name' => $data['grandfather_name'] ?? '',
            'guardian_name'    => $data['guardian_name']    ?? '',
            'guardian_contact' => $data['guardian_contact'] ?? '',
            'parent_contact'   => $data['parent_contact']   ?? '',
            'address_en'       => $data['address_en']       ?? '',
            'registration_no'  => $data['registration_no']  ?? '',
            'citizenship_no'   => $data['citizenship_no']   ?? '',
            'blood_group'      => $data['blood_group']      ?? '',
            'member_type'      => $data['member_type']      ?? '',
            'stream'           => $data['stream']           ?? '',
            'section'          => $data['section']          ?? '',
            'designation'      => $data['designation']      ?? '',
            'employment_type'  => $data['employment_type']  ?? '',
            'joining_date'     => $data['joining_date']     ?? '',
            'permanent_date'   => $data['permanent_date']   ?? '',
            'bank_name'        => $data['bank_name']        ?? '',
            'bank_branch'      => $data['bank_branch']      ?? '',
            'bank_account_name'   => $data['bank_account_name']   ?? '',
            'bank_account_number' => $data['bank_account_number'] ?? '',
            'pan_number'       => $data['pan_number']       ?? '',
            'device_id'        => $data['device_id']        ?? '',
            'program'          => $data['program']          ?? '',
            'batch'            => $data['batch']            ?? '',
            'password'         => $data['password']         ?? '',
            'organization'     => $defaultOrg,
            'error'            => $error,
        ];
    }

    private function showImportPreview(array $rows, Request $request): \Illuminate\View\View
    {
        $context = [
            'organization'         => $request->organization,
            'member_type'          => $request->member_type,
            'stream'               => $request->stream,
            'section'              => $request->section,
            'employee_category'    => $request->employee_category,
            'designation_id'       => $request->designation_id,
            'employment_type_id'   => $request->employment_type_id,
            'work_assigned_id'     => $request->work_assigned_id,
            'hajiri_department_id' => $request->hajiri_department_id,
            'import_kind'          => $request->input('import_kind', 'standard'),
        ];

        session(['hr_import_rows' => $rows, 'hr_import_context' => $context]);

        return view('hr.members.import', [
            'rows'          => $rows,
            'context'       => $context,
            'formOptions'   => $this->buildFormOptions(),
            'hajiriOptions' => $this->hajiriOptions(),
        ]);
    }

    private function bsToAdDate(string $bsDate): ?string
    {
        if (!preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', trim($bsDate), $m)) return null;
        [$bsYear, $bsMonth, $bsDay] = [(int)$m[1], (int)$m[2], (int)$m[3]];
        if ($bsMonth <= 9) { $adYear = $bsYear - 57; $adMonth = $bsMonth + 3; }
        else               { $adYear = $bsYear - 56; $adMonth = $bsMonth - 9; }
        try {
            $maxDay = (int) Carbon::createFromDate($adYear, $adMonth, 1)->endOfMonth()->day;
            return sprintf('%04d-%02d-%02d', $adYear, $adMonth, min($bsDay, $maxDay));
        } catch (\Throwable) { return null; }
    }

    private function generateLoginId(string $org, string $stream, string $section, string $roll): string
    {
        do {
            $id = $this->generateLoginIdCandidate($org);
        } while (User::where('student_code', $id)->exists());

        return $id;
    }

    /**
     * Generate a login ID without a database query. Import previews keep an
     * in-memory set and perform one batched database lookup after parsing.
     */
    private function generateLoginIdCandidate(string $org): string
    {
        // Org slug → abbreviation: "godawari-college" → "GC"
        $orgCode = strtoupper(implode('', array_map(
            fn ($w) => $w[0] ?? '',
            preg_split('/[-_ ]+/', $org)
        )));
        if (\strlen($orgCode) < 2) {
            $orgCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $org), 0, 4));
        }

        // Unambiguous charset — no O/0 or I/1 confusion when read on paper
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $len   = \strlen($chars);

        $random = '';
        for ($i = 0; $i < 6; $i++) {
            $random .= $chars[random_int(0, $len - 1)];
        }

        return $orgCode . '-' . $random;
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;
        foreach (['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d'] as $fmt) {
            try { return Carbon::createFromFormat($fmt, $value)->format('Y-m-d'); } catch (\Throwable) {}
        }
        try { return Carbon::parse($value)->format('Y-m-d'); } catch (\Throwable) { return null; }
    }

    private function normalizeSpreadsheetHeader(mixed $value): string
    {
        $header = strtolower(trim((string) $value));
        $header = str_replace(['&', '/', '-'], ['and', ' ', ' '], $header);
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);

        return trim(preg_replace('/_+/', '_', $header), '_');
    }

    private function splitImportName(string $fullName): array
    {
        $parts = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) === 0) {
            return ['', '', ''];
        }
        if (count($parts) === 1) {
            // The HR schema requires both names; retain a single-part name
            // verbatim in both fields instead of silently rejecting it.
            return [$parts[0], '', $parts[0]];
        }

        return [
            array_shift($parts),
            count($parts) > 1 ? implode(' ', array_slice($parts, 0, -1)) : '',
            end($parts),
        ];
    }

    private function normalizeImportGender(string $gender): string
    {
        $normalized = strtolower(trim($gender));

        return match ($normalized) {
            'm', 'male', 'boy' => 'Male',
            'f', 'female', 'girl' => 'Female',
            'o', 'other', 'others' => 'Other',
            default => trim($gender),
        };
    }

    private function normalizeBsDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (! preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $value, $matches)) {
            return null;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];
        $day = (int) $matches[3];
        if ($year < 1900 || $year > 2200 || $month < 1 || $month > 12 || $day < 1 || $day > 32) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    public function template()
    {
        $headers = [
            'roll_number', 'login_user_id', 'member_type', 'first_name', 'middle_name', 'last_name',
            'father_name', 'mother_name', 'grandfather_name', 'guardian_name',
            'guardian_relation', 'guardian_contact',
            'dob', 'gender', 'blood_group', 'mobile', 'parent_contact', 'email',
            'stream', 'section', 'designation', 'employment_type', 'employee_category',
            'joining_date', 'permanent_date', 'device_id', 'bank_name', 'bank_branch',
            'bank_account_name', 'bank_account_number', 'pan_number',
            'permanent_province', 'permanent_district', 'permanent_municipality',
            'temporary_province', 'temporary_district', 'temporary_municipality',
            'password',
        ];

        return response(implode(',', $headers) . "\n", 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="hr-members-template.csv"',
        ]);
    }

    private function hajiriOptions(): array
    {
        return [
            'designations' => Designation::orderBy('label')->get(),
            'employmentTypes' => EmploymentType::orderBy('label')->get(),
            'workAssigned' => WorkAssigned::orderBy('label')->get(),
            'departments' => HajiriDepartment::orderBy('label')->get(),
        ];
    }

    private function hajiriData(Request $request): array
    {
        $workAreaLabel = match ($request->input('member_type')) {
            'teacher' => 'Academic',
            'staff' => 'Administration',
            default => null,
        };

        // Employee type already expresses this classification in HR. Mirror it
        // to Hajiri's legacy column so attendance reports remain compatible.
        $workAssignedId = $workAreaLabel
            ? WorkAssigned::where('label', $workAreaLabel)->value('id')
            : null;

        return [
            'designation_id' => $request->input('designation_id'),
            'employment_type_id' => $request->input('employment_type_id'),
            'work_assigned_id' => $workAssignedId,
            'hajiri_department_id' => $request->input('hajiri_department_id'),
        ];
    }

    private function optionalForeignIdRules(string $table): array
    {
        return array_filter([
            'nullable',
            'integer',
            Schema::hasTable($table) ? Rule::exists($table, 'id') : null,
        ]);
    }

    private function buildFormOptions(): array
    {
        $options = [];

        if (Schema::hasTable('organizations') && Schema::hasTable('departments') && Schema::hasTable('sections')) {
            $options = CardOrganization::query()
                ->with(['departments.sections'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->mapWithKeys(function (CardOrganization $organization) {
                    return [
                        $organization->slug => [
                            'label' => $organization->name,
                            'academic_systems' => $organization->departments
                                ->where('is_active', true)
                                ->mapWithKeys(fn ($department) => [$department->name => $department->academic_system ?? 'none'])
                                ->all(),
                            'streams' => $organization->departments
                                ->where('is_active', true)
                                ->mapWithKeys(fn ($department) => [
                                    $department->name => $department->sections
                                        ->where('is_active', true)
                                        ->pluck('name')
                                        ->values()
                                        ->all(),
                                ])
                                ->all(),
                        ],
                    ];
                })
                ->all();
        }

        $existingOptions = Student::query()
            ->select('organization', 'stream', 'section')
            ->whereNotNull('organization')
            ->where('organization', '!=', '')
            ->orderBy('organization')
            ->orderBy('stream')
            ->orderBy('section')
            ->get()
            ->groupBy('organization')
            ->map(fn (Collection $rows, string $organization) => [
                'label' => ucwords(str_replace(['-', '_'], ' ', $organization)),
                'streams' => $rows->groupBy('stream')
                    ->map(fn (Collection $streamRows) => $streamRows->pluck('section')->filter()->unique()->values()->all())
                    ->all(),
            ])
            ->all();

        foreach ($existingOptions as $organization => $organizationData) {
            $options[$organization]['label'] ??= $organizationData['label'];

            foreach ($organizationData['streams'] ?? [] as $stream => $sections) {
                if (blank($stream)) {
                    continue;
                }

                $currentSections = $options[$organization]['streams'][$stream] ?? [];
                $options[$organization]['streams'][$stream] = collect($currentSections)
                    ->merge($sections)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }
        }

        ksort($options);

        return $options;
    }

    private function storePhoto(Request $request, string $code): string
    {
        File::ensureDirectoryExists(public_path('photos'));
        $file = $request->file('photo');
        $filename = $code . '_' . time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('photos'), $filename);

        return "photos/{$filename}";
    }

    private function deletePhoto(?string $path): void
    {
        if ($path && str_starts_with($path, 'photos/') && File::exists(public_path($path))) {
            File::delete(public_path($path));
        }
    }

    private function storeBase64Photo(string $base64, string $code): string
    {
        // Strip data URI prefix: "data:image/jpeg;base64,..."
        $data     = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $decoded  = base64_decode($data);
        if ($decoded === false) {
            return '';
        }
        File::ensureDirectoryExists(public_path('photos'));
        $filename = $code . '_' . time() . '.jpg';
        File::put(public_path("photos/{$filename}"), $decoded);
        return "photos/{$filename}";
    }

    public function getMunicipalitiesByDistrict($district)
    {
        $municipalities = Student::query()
            ->where('permanent_district', $district)
            ->whereNotNull('permanent_municipality')
            ->where('permanent_municipality', '!=', '')
            ->distinct()
            ->orderBy('permanent_municipality')
            ->pluck('permanent_municipality');

        return response()->json($municipalities->values());
    }

    public function getSectionsByStream($stream)
    {
        $sections = CardSection::query()
            ->whereHas('department', fn ($d) => $d->where('name', $stream))
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();

        return response()->json($sections);
    }
}
