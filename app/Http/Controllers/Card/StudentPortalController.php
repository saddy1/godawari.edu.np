<?php

namespace App\Http\Controllers\Card;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Hajiri\NepaliCalendarController;

use App\Models\Card\Student;
use App\Models\Card\CardRequest;
use App\Models\Card\UpdateRequest;
use App\Models\Hajiri\AttendanceLogs;
use App\Models\Hajiri\HajiriSetting;
use App\Models\Hajiri\Holiday;
use App\Models\Learning\LearningCourse;
use App\Models\Learning\LearningResource;
use App\Models\Learning\LearningProgress;
use App\Models\LibraryLoan;
use App\Models\LibraryNotification;
use App\Support\SiteSettings;
use App\Services\Hajiri\AttendanceWindow;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $student = $this->getStudent()->load('user');
        $deviceId = $student->user?->device_id;
        $calendar = new NepaliCalendarController();
        $today = Carbon::today();
        $todayBs = $calendar->ad_2_bs($today->year, $today->month, $today->day);

        abort_unless(is_array($todayBs), 500, 'The Nepali calendar is not available for the current date.');

        $minYear = $calendar->minBsYear();
        $maxYear = $calendar->maxBsYear();
        $currentYear = (int) $todayBs['year'];
        $currentMonth = (int) $todayBs['month'];
        $year = $request->integer('year', $currentYear);
        $month = $request->integer('month', $currentMonth);

        if ($year < $minYear || $year > $maxYear) {
            $year = $currentYear;
        }
        if ($month < 1 || $month > 12) {
            $month = $currentMonth;
        }

        $calendarYear = $calendar->getBSCal($year);
        abort_unless($calendarYear, 404, 'The selected Nepali calendar year is unavailable.');

        $daysInMonth = (int) $calendarYear[$month];
        $firstAd = $calendar->bs_2_ad($year, $month, 1);
        $lastAd = $calendar->bs_2_ad($year, $month, $daysInMonth);
        abort_unless(is_array($firstAd) && is_array($lastAd), 404, 'The selected month is unavailable.');

        $startDate = Carbon::createFromDate((int) $firstAd['year'], (int) $firstAd['month'], (int) $firstAd['date'])->startOfDay();
        $endDate = Carbon::createFromDate((int) $lastAd['year'], (int) $lastAd['month'], (int) $lastAd['date'])->endOfDay();
        $logsByDate = collect();

        if (filled($deviceId) && Schema::hasTable('attendacelogs')) {
            $logsByDate = AttendanceLogs::query()
                ->where('user_id', (int) $deviceId)
                ->whereBetween('at', [$startDate, $endDate])
                ->orderBy('at')
                ->get()
                ->groupBy(fn ($log) => Carbon::parse($log->getRawOriginal('at'))->toDateString());
        }

        $holidays = collect();
        if (Schema::hasTable('holiday')) {
            $holidayQuery = Holiday::query()
                ->whereDate('date', '>=', $startDate->toDateString())
                ->whereDate('date', '<=', $endDate->toDateString());

            if (Schema::hasColumn('holiday', 'status')) {
                $holidayQuery->where('status', true);
            }

            $holidays = $holidayQuery->get()->keyBy(fn ($holiday) => $holiday->date->toDateString());
        }

        $setting = HajiriSetting::current();
        $rows = collect();

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $ad = $calendar->bs_2_ad($year, $month, $day);
            $date = Carbon::createFromDate((int) $ad['year'], (int) $ad['month'], (int) $ad['date']);
            $dateKey = $date->toDateString();
            $dayLogs = $logsByDate->get($dateKey, collect());
            $holiday = $holidays->get($dateKey);
            $isWeekend = $setting->isWeekend($date->dayOfWeek);
            $attendance = $dayLogs->isNotEmpty() ? AttendanceWindow::summary($dayLogs, $setting) : null;

            if ($dayLogs->isNotEmpty()) {
                $status = 'present';
                $statusLabel = 'Present';
            } elseif ($holiday) {
                $status = 'holiday';
                $statusLabel = $holiday->label ?: 'Holiday';
            } elseif ($isWeekend) {
                $status = 'weekend';
                $statusLabel = 'Weekend';
            } elseif ($date->isFuture()) {
                $status = 'upcoming';
                $statusLabel = 'Upcoming';
            } elseif ($date->isToday()) {
                $status = 'pending';
                $statusLabel = 'Not recorded';
            } else {
                $status = 'absent';
                $statusLabel = 'Absent';
            }

            $rows->push([
                'bs_date' => sprintf('%04d-%02d-%02d', $year, $month, $day),
                'ad_date' => $date->format('d M Y'),
                'weekday' => $date->format('l'),
                'status' => $status,
                'status_label' => $statusLabel,
                'check_in' => $attendance['in']['time'] ?? '—',
                'check_out' => $dayLogs->count() > 1 ? ($attendance['out']['time'] ?? '—') : '—',
                'in_valid' => $attendance['in']['valid'] ?? null,
                'out_valid' => $dayLogs->count() > 1 ? ($attendance['out']['valid'] ?? null) : null,
                'punches' => $dayLogs->count(),
            ]);
        }

        $present = $rows->where('status', 'present')->count();
        $absent = $rows->where('status', 'absent')->count();
        $workingDays = $present + $absent;
        $summary = [
            'present' => $present,
            'absent' => $absent,
            'holidays' => $rows->whereIn('status', ['holiday', 'weekend'])->count(),
            'rate' => $workingDays > 0 ? round(($present / $workingDays) * 100, 1) : 0,
        ];

        $monthNames = [
            1 => 'Baisakh', 2 => 'Jestha', 3 => 'Ashadh', 4 => 'Shrawan',
            5 => 'Bhadra', 6 => 'Ashwin', 7 => 'Kartik', 8 => 'Mangsir',
            9 => 'Poush', 10 => 'Magh', 11 => 'Falgun', 12 => 'Chaitra',
        ];
        $supportedYears = range(max($minYear, $currentYear - 4), min($maxYear, $currentYear + 1));

        return view('card.student-portal.attendance', compact(
            'student', 'deviceId', 'year', 'month', 'monthNames', 'supportedYears', 'rows', 'summary'
        ));
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
        $query = CardRequest::with('student')
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'collected', 'rejected')")
            ->orderBy('created_at', 'desc');

        // Scope to admin's organization
        if (!auth()->user()->isSuperAdmin()) {
            $query->whereHas('student', fn($q) => auth()->user()->applyStudentScope($q));
        }

        $requests = $query->paginate(25);

        return view('card.admin.card-requests', compact('requests'));
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
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderBy('created_at', 'desc');

        if (!auth()->user()->isSuperAdmin()) {
            $query->whereHas('student', fn($q) => auth()->user()->applyStudentScope($q));
        }

        $requests = $query->paginate(25);

        return view('card.admin.update-requests', compact('requests'));
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
}
