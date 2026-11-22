<?php

namespace App\Http\Controllers\Card;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Hajiri\NepaliCalendarController;

use App\Models\Card\Student;
use App\Models\Card\CardRequest;
use App\Models\Card\UpdateRequest;
use App\Models\Hajiri\HajiriSetting;
use App\Models\Hajiri\Holiday;
use App\Models\Learning\LearningCourse;
use App\Models\Learning\LearningResource;
use App\Models\Learning\LearningProgress;
use App\Models\LibraryLoan;
use App\Models\LibraryNotification;
use App\Support\SiteSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StudentPortalController extends Controller
{
    // ── Helpers ────────────────────────────────────────────────────────────

    private function getStudent(): Student
    {
        return Student::findOrFail(session('student_id'));
    }

    private function profileCompletionRequired(Student $student): bool
    {
        return $student->profile_completed_at === null
            && ! $student->updateRequests()->where('status', 'pending')->exists();
    }

    private function removePendingProfilePhoto(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'profile-update-photos/')) {
            return;
        }

        $absolutePath = public_path($path);
        if (File::isFile($absolutePath)) {
            File::delete($absolutePath);
        }
    }

    private function approvePendingProfilePhoto(Student $student, string $path): ?string
    {
        if (! str_starts_with($path, 'profile-update-photos/')) {
            return null;
        }

        $source = public_path($path);
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        if (! File::isFile($source) || ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        File::ensureDirectoryExists(public_path('photos'));
        $filename = 'student-' . $student->id . '-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(6)) . '.' . $extension;
        File::move($source, public_path('photos/' . $filename));

        return 'photos/' . $filename;
    }

    private function bsDateToAdDate(?string $value): ?string
    {
        if (! $value || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)) {
            return null;
        }

        $converted = (new NepaliCalendarController())->bs_2_ad((int) $parts[1], (int) $parts[2], (int) $parts[3]);
        if (! is_array($converted)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $converted['year'], $converted['month'], $converted['date']);
    }

    // ── Student Portal ─────────────────────────────────────────────────────

    public function dashboard()
    {
        $student      = $this->getStudent();

        if ($this->profileCompletionRequired($student)) {
            return redirect()->route('student.request-update')
                ->with('info', 'Please review your contact and address details before continuing.');
        }

        $cardRequest  = CardRequest::where('student_id', $student->id)->latest()->first();
        $updateRequest = UpdateRequest::where('student_id', $student->id)
            ->where('status', 'pending')->latest()->first();
        $courseCount = LearningCourse::query()
            ->published()
            ->when($student->stream, fn ($query) => $query->whereHas('learningClass', fn ($classQuery) => $classQuery->where('name', $student->stream)))
            ->count();
        $resourceCount = LearningResource::query()
            ->where('is_published', true)
            ->when($student->stream, fn ($query) => $query->whereHas('learningClass', fn ($classQuery) => $classQuery->where('name', $student->stream)))
            ->count();

        // Library data (only if module exists)
        $libraryIssuedCount  = 0;
        $libraryOverdueCount = 0;
        $libraryFineOwed     = 0;
        $libraryNotifCount   = 0;
        $libraryActiveLoans  = collect();

        if (Schema::hasTable('library_loans')) {
            $userId = $student->user_id;
            $baseQuery = fn () => LibraryLoan::where(function ($q) use ($student, $userId) {
                $q->where('student_id', $student->id);
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
            });

            $libraryActiveLoans  = (clone $baseQuery())->with('copy.book')->where('status', 'issued')->latest('issued_at')->get();
            $libraryIssuedCount  = $libraryActiveLoans->count();
            $libraryOverdueCount = $libraryActiveLoans->filter(fn ($l) => $l->due_date && $l->due_date->isPast())->count();
            $libraryFineOwed     = $libraryActiveLoans->sum(fn ($l) => $l->accrued_fine);
            $libraryActiveLoans  = $libraryActiveLoans->take(3);

            if ($userId && Schema::hasTable('library_notifications')) {
                $libraryNotifCount = LibraryNotification::where('user_id', $userId)->where('is_read', false)->count();
            }
        }

        return view('card.student-portal.dashboard', compact(
            'student', 'cardRequest', 'updateRequest', 'courseCount', 'resourceCount',
            'libraryIssuedCount', 'libraryOverdueCount', 'libraryFineOwed', 'libraryNotifCount', 'libraryActiveLoans'
        ));
    }

    public function attendance(Request $request)
    {
        $student = $this->getStudent();
        $data = app(\App\Services\StudentAttendanceCalendarService::class)->forAcademicYear($student, $request->integer('academic_year_id') ?: null);

        return view('card.student-portal.attendance', array_merge($data, ['student' => $student]));
    }

    // "My Exams" — every exam this student has been issued a symbol number
    // for, each with its exam-day
    // attendance (as marked by the invigilating teacher — separate from the
    // biometric device attendance on the Attendance page), and its marksheet
    // once the school publishes results for the student's own faculty.
    public function results()
    {
        $student = $this->getStudent();
        $departmentId = $student->department_record?->id;

        $symbolNumbers = \App\Models\Examination\ExaminationSymbolNumber::where('student_id', $student->id)
            ->pluck('symbol_no', 'examination_id');
        $examinations = \App\Models\Examination\Examination::with(['academicYear', 'organization'])
            ->whereIn('id', $symbolNumbers->keys())
            ->orderByDesc('starts_on')
            ->get();

        $attendanceByExam = \App\Models\Examination\ExaminationAttendance::with(['examinationSubject.offering.subject', 'markedBy'])
            ->where('student_id', $student->id)
            ->whereIn('examination_id', $symbolNumbers->keys())
            ->orderBy('exam_date')
            ->get()
            ->groupBy('examination_id');

        $resultService = app(\App\Services\ExamResultService::class);
        $results = $examinations->map(function ($examination) use ($student, $symbolNumbers, $attendanceByExam, $resultService, $departmentId) {
            $symbolNo = (int) $symbolNumbers->get($examination->id, 0);
            $published = $examination->isPublishedForDepartment($departmentId);

            return (object) [
                'examination' => $examination,
                'symbol_no' => $symbolNo,
                'published' => $published,
                'result' => $published ? $resultService->resultFor($examination, $student, $symbolNo) : null,
                'attendance' => $attendanceByExam->get($examination->id, collect()),
            ];
        })->values();

        return view('card.student-portal.results', compact('student', 'results'));
    }

    // View-only — no download/print affordance. Just the standard,
    // official-format marksheet to look at, gated on the result being
    // published for the student's own faculty (same check results() uses).
    public function marksheet(\App\Models\Examination\Examination $examination)
    {
        $student = $this->getStudent();
        $row = $this->studentMarksheetRow($examination, $student);

        return view('examinations.marksheets.print', [
            'examination' => $examination,
            'roster' => collect([$row]),
            'backUrl' => route('student.results'),
            'readOnly' => true,
        ]);
    }

    private function studentMarksheetRow(\App\Models\Examination\Examination $examination, Student $student): array
    {
        abort_unless($examination->isPublishedForDepartment($student->department_record?->id), 404, 'This result has not been published yet.');
        $examination->loadMissing(['organization.logoAsset', 'organization.signatureAsset', 'organization.stampAsset', 'academicYear']);
        $symbolNo = (int) \App\Models\Examination\ExaminationSymbolNumber::where('examination_id', $examination->id)
            ->where('student_id', $student->id)->value('symbol_no');
        abort_if(! $symbolNo, 404, 'No symbol number assigned for this exam.');

        return app(\App\Services\ExamResultService::class)->resultFor($examination, $student, $symbolNo);
    }

    public function learning()
    {
        $student = $this->getStudent();
        $user = $student->user;

        $courses = LearningCourse::query()
            ->with(['learningClass', 'subject', 'lessons' => fn ($query) => $query->where('is_published', true)])
            ->published()
            ->when($student->stream, fn ($query) => $query->whereHas('learningClass', fn ($classQuery) => $classQuery->where('name', $student->stream)))
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $progress = $user
            ? LearningProgress::query()
                ->where('user_id', $user->id)
                ->whereNull('learning_lesson_id')
                ->pluck('progress_percent', 'learning_course_id')
            : collect();

        $resources = LearningResource::query()
            ->with(['learningClass', 'subject'])
            ->where('is_published', true)
            ->when($student->stream, fn ($query) => $query->whereHas('learningClass', fn ($classQuery) => $classQuery->where('name', $student->stream)))
            ->latest()
            ->take(12)
            ->get();

        return view('card.student-portal.learning', compact('student', 'courses', 'progress', 'resources'));
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $student = $this->getStudent();
        if ($student->updateRequests()->where('status', 'pending')->exists()) {
            return redirect()->route('student.request-update')
                ->with('info', 'Your existing profile update request is already pending approval.');
        }

        $photo = $request->file('photo');
        File::ensureDirectoryExists(public_path('profile-update-photos'));
        $filename = Str::uuid() . '.' . strtolower($photo->getClientOriginalExtension());
        $photo->move(public_path('profile-update-photos'), $filename);

        UpdateRequest::create([
            'student_id' => $student->id,
            'requested_changes' => ['photo' => 'profile-update-photos/' . $filename],
            'previous_values' => ['photo' => $student->photo],
            'status' => 'pending',
        ]);

        if ($student->profile_completed_at === null) {
            $student->update(['profile_completed_at' => now()]);
        }

        return redirect()->route('student.dashboard')
            ->with('success', 'Profile photo submitted for administration approval.');
    }

    public function requestCard(Request $request)
    {
        $student = $this->getStudent();

        $existing = CardRequest::where('student_id', $student->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            return redirect()->route('student.card-status')
                ->with('info', 'You already have a card request in progress.');
        }

        CardRequest::create([
            'student_id' => $student->id,
            'status'     => 'pending',
        ]);

        return redirect()->route('student.card-status')
            ->with('success', 'Card request submitted! Please complete payment and visit the admin office.');
    }

    public function cardStatus()
    {
        $student     = $this->getStudent();
        $cardRequest = CardRequest::where('student_id', $student->id)->latest()->first();

        return view('card.student-portal.request-card', compact('student', 'cardRequest'));
    }

    public function requestUpdateForm()
    {
        $student = $this->getStudent();
        $pending = UpdateRequest::where('student_id', $student->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        $isOnboarding = $student->profile_completed_at === null;

        return view('card.student-portal.request-update', compact('student', 'pending', 'isOnboarding'));
    }

    public function submitUpdate(Request $request)
    {
        $student = $this->getStudent();

        $pending = UpdateRequest::where('student_id', $student->id)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            if ($student->profile_completed_at === null) {
                $student->update(['profile_completed_at' => now()]);
            }

            return redirect()->route('student.dashboard')->with('info', 'Your profile update request is already pending approval.');
        }

        $data = $request->validate([
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'dob_bs' => ['nullable', 'regex:/^\d{4}-\d{2}-\d{2}$/'],
            'father_name' => ['nullable', 'string', 'max:150'],
            'mother_name' => ['nullable', 'string', 'max:150'],
            'grandfather_name' => ['nullable', 'string', 'max:150'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'parent_contact' => ['nullable', 'string', 'max:30'],
            'guardian_contact' => ['nullable', 'string', 'max:30'],
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

        if (filled($data['dob_bs'] ?? null)) {
            $dobAd = $this->bsDateToAdDate($data['dob_bs']);
            if (! $dobAd || $dobAd > now()->toDateString()) {
                return back()->withInput()->withErrors([
                    'dob_bs' => 'Please select a valid, non-future BS date of birth from the Nepali calendar.',
                ]);
            }
        }

        $photo = $data['photo'] ?? null;
        unset($data['photo']);

        $changes = collect($data)
            ->filter(fn ($value, $field) => (string) $student->{$field} !== (string) $value)
            ->all();

        if ($photo) {
            File::ensureDirectoryExists(public_path('profile-update-photos'));
            $extension = strtolower($photo->getClientOriginalExtension());
            $filename = Str::uuid() . '.' . $extension;
            $photo->move(public_path('profile-update-photos'), $filename);
            $changes['photo'] = 'profile-update-photos/' . $filename;
        }

        if (empty($changes)) {
            if ($student->profile_completed_at === null) {
                $student->update(['profile_completed_at' => now()]);

                return redirect()->route('student.dashboard')->with('success', 'Your profile details have been reviewed.');
            }

            return back()->with('info', 'Please enter at least one new value before submitting.');
        }

        UpdateRequest::create([
            'student_id' => $student->id,
            'requested_changes' => $changes,
            'previous_values' => collect(array_keys($changes))
                ->mapWithKeys(fn ($field) => [$field => $student->{$field}])
                ->all(),
            'status' => 'pending',
        ]);

        if ($student->profile_completed_at === null) {
            $student->update(['profile_completed_at' => now()]);
        }

        return redirect()->route('student.dashboard')->with('success', 'Profile update request submitted for admin review.');
    }

    public function editProfile()
    {
        return redirect()->route('student.request-update');
    }

    public function updateProfile(Request $request)
    {
        return redirect()->route('student.request-update')
            ->with('info', 'Profile changes must be submitted for administration approval.');
    }

    // ── Admin: Card Requests ───────────────────────────────────────────────

    public function adminCardRequests()
    {
        // Ignore legacy requests whose student was deleted. Besides keeping the
        // queue meaningful, this prevents a missing relationship from breaking
        // the whole admin page.
        $query = CardRequest::with('student')
            ->whereHas('student')
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'collected', 'rejected')")
            ->orderBy('created_at', 'desc');

        // Scope to admin's organization
        if (!auth()->user()->isSuperAdmin()) {
            $query->whereHas('student', fn($q) => auth()->user()->applyStudentScope($q));
        }

        $requests = $query->paginate(25);
        $orphanedRequestCount = CardRequest::doesntHave('student')->count();

        return view('card.admin.card-requests', compact('requests', 'orphanedRequestCount'));
    }

    /** Remove only card requests whose linked student no longer exists. */
    public function adminCleanupOrphanedCardRequests()
    {
        $deleted = CardRequest::doesntHave('student')->delete();

        return redirect()->route('admin.card-requests')->with(
            'success',
            $deleted === 1
                ? 'Removed 1 invalid card request with no linked student.'
                : "Removed {$deleted} invalid card requests with no linked student."
        );
    }

    public function adminUpdateCardRequest(Request $request, CardRequest $cardRequest)
    {
        $request->validate([
            'status'     => 'required|in:pending,approved,collected,rejected',
            'admin_note' => 'nullable|string|max:500',
        ]);

        $cardRequest->update([
            'status'     => $request->status,
            'admin_note' => $request->admin_note,
        ]);

        return back()->with('success', 'Card request updated.');
    }

    // ── Admin: Update Requests ─────────────────────────────────────────────

    public function adminUpdateRequests()
    {
        $query = UpdateRequest::with('student')
            // Do not render a request whose student was permanently deleted.
            ->whereHas('student')
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderBy('created_at', 'desc');

        if (!auth()->user()->isSuperAdmin()) {
            $query->whereHas('student', fn($q) => auth()->user()->applyStudentScope($q));
        }

        $requests = $query->paginate(25);

        return view('card.admin.update-requests', compact('requests'));
    }

    /** Display one request without crowding the administrative queue. */
    public function adminShowUpdateRequest(UpdateRequest $updateRequest)
    {
        $updateRequest->load('student');
        abort_unless($updateRequest->student, 404);

        if (! auth()->user()->isSuperAdmin()) {
            $isInScope = auth()->user()->applyStudentScope(
                Student::withoutGlobalScope('active')->whereKey($updateRequest->student_id)
            )->exists();

            abort_unless($isInScope, 403);
        }

        return view('card.admin.update-request-show', compact('updateRequest'));
    }

    public function adminReviewUpdateRequest(Request $request, UpdateRequest $updateRequest)
    {
        $request->validate([
            'action'     => 'required|in:approve,reject',
            'admin_note' => 'nullable|string|max:500',
        ]);

        if ($updateRequest->status !== 'pending') {
            return back()->with('info', 'This update request has already been reviewed.');
        }

        if ($request->action === 'approve') {
            // Apply only safe, allowed fields
            $allowed = [
                'dob_bs', 'father_name', 'mother_name', 'grandfather_name',
                'mobile', 'email', 'parent_contact', 'guardian_contact',
                'permanent_province', 'permanent_district', 'permanent_municipality', 'permanent_ward', 'permanent_tole',
                'temporary_province', 'temporary_district', 'temporary_municipality', 'temporary_ward', 'temporary_tole',
                'zone', 'district', 'municipality',
            ];
            $requestedChanges = $updateRequest->requested_changes ?? [];
            $pendingPhoto = $requestedChanges['photo'] ?? null;
            unset($requestedChanges['photo']);

            $changes = array_intersect_key($requestedChanges, array_flip($allowed));

            if (array_key_exists('dob_bs', $changes)) {
                if (filled($changes['dob_bs'])) {
                    $dobAd = $this->bsDateToAdDate($changes['dob_bs']);
                    if (! $dobAd || $dobAd > now()->toDateString()) {
                        return back()->with('error', 'The requested BS date of birth is invalid. The request was not approved.');
                    }
                    $changes['dob'] = $dobAd;
                } else {
                    $changes['dob'] = null;
                }
            }

            if ($pendingPhoto) {
                $approvedPhoto = $this->approvePendingProfilePhoto($updateRequest->student, $pendingPhoto);
                if (! $approvedPhoto) {
                    return back()->with('error', 'The requested profile photo is missing or invalid. The request was not approved.');
                }
                $changes['photo'] = $approvedPhoto;
            }

            $updateRequest->student->update($changes);
            $updateRequest->update(['status' => 'approved', 'admin_note' => $request->admin_note]);
        } else {
            $this->removePendingProfilePhoto($updateRequest->requested_changes['photo'] ?? null);
            $updateRequest->update(['status' => 'rejected', 'admin_note' => $request->admin_note]);
        }

        return back()->with('success', 'Update request ' . $request->action . 'd successfully.');
    }

    public function myLibrary()
    {
        $student = $this->getStudent();
        $userId  = $student->user_id;

        $baseQuery = fn () => LibraryLoan::where(function ($q) use ($student, $userId) {
            $q->where('student_id', $student->id);
            if ($userId) {
                $q->orWhere('user_id', $userId);
            }
        });

        $activeLoans  = (clone $baseQuery())->with('copy.book')->where('status', 'issued')->orderBy('due_date')->get();
        $historyLoans = (clone $baseQuery())->with('copy.book')->where('status', 'returned')->latest('returned_at')->paginate(10)->withQueryString();
        $overdueCount = $activeLoans->filter(fn ($l) => $l->due_date && $l->due_date->isPast())->count();
        $fineOwed     = $activeLoans->sum(fn ($l) => $l->accrued_fine);

        // Mark notifications as read
        if ($userId && \Illuminate\Support\Facades\Schema::hasTable('library_notifications')) {
            \App\Models\LibraryNotification::where('user_id', $userId)->where('is_read', false)->update(['is_read' => true]);
        }

        return view('card.student-portal.library', compact('student', 'activeLoans', 'historyLoans', 'overdueCount', 'fineOwed'));
    }

    public function changePasswordForm()
    {
        return view('card.student-portal.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'      => ['required', 'current_password'],
            'password'              => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)],
        ]);

        $request->user()->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        return back()->with('success', 'Password changed successfully.');
    }

    public function complaints()
    {
        $student = $this->getStudent();
        $complaints = \App\Models\Complaint::where('student_id', $student->id)->latest()->get();

        return view('card.student-portal.complaints', compact('student', 'complaints'));
    }

    public function submitComplaint(Request $request)
    {
        $student = $this->getStudent();
        $data = $request->validate([
            'category' => ['required', \Illuminate\Validation\Rule::in(array_keys(\App\Models\Complaint::CATEGORIES))],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
        ]);

        \App\Models\Complaint::create([...$data, 'student_id' => $student->id, 'status' => 'pending']);

        return redirect()->route('student.complaints')->with('success', 'Your feedback/complaint has been submitted.');
    }
}
