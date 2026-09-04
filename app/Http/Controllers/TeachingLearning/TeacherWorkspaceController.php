<?php

namespace App\Http\Controllers\TeachingLearning;

use App\Http\Controllers\Controller;
use App\Models\Examination\ExaminationSubject;
use App\Models\Examination\ExaminationMarkSubmission;
use App\Models\TeachingLearning\RoutineAttendanceSession;
use App\Models\TeachingLearning\RoutineLesson;
use App\Models\TeachingLearning\RoutineStudentAttendance;
use App\Models\User;
use App\Services\ExamTeacherAssignmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class TeacherWorkspaceController extends Controller
{
    private const OPENS_BEFORE_MINUTES = 10;
    private const CLOSES_AFTER_MINUTES = 15;

    public function index(Request $request, ExamTeacherAssignmentService $examAssignments)
    {
        $user = $request->user();
        abort_unless($user->isTeacher() || $user->canAccess([
            'examinations.manage', 'examinations.marks.enter', 'teaching-learning.routine.manage',
        ]), 403);

        $today = now()->toDateString();
        $markEntries = collect();
        if ($user->canAccess('examinations.marks.enter')) {
            $subjects = ExaminationSubject::query()
                ->with(['examination.academicYear', 'examination.sections', 'offering.subject', 'offering.department', 'marks'])
                ->whereHas('examination', fn ($query) => $query->where('status', 'ongoing')
                    ->where(fn ($dates) => $dates->whereNull('starts_on')->orWhereDate('starts_on', '<=', $today))
                    ->where(fn ($dates) => $dates->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today)))
                ->get();

            foreach ($subjects as $subject) {
                $theorySections = (float) $subject->theory_full_marks > 0
                    ? $examAssignments->sectionIdsFor($subject, $user, 'theory') : collect();
                $practicalSections = (float) $subject->practical_full_marks > 0
                    ? $examAssignments->sectionIdsFor($subject, $user, 'practical') : collect();
                $sectionIds = $theorySections->merge($practicalSections)->unique()->values();
                if ($sectionIds->isEmpty()) continue;

                $markEntries->push((object) [
                    'subject' => $subject,
                    'examination' => $subject->examination,
                    'entry_component' => $theorySections->isNotEmpty() ? 'theory' : 'practical',
                    'sections' => $subject->examination->sections->whereIn('id', $sectionIds)->pluck('name')->implode(', '),
                    'entered' => $subject->marks->filter(fn ($mark) => $mark->theory_marks !== null
                        || $mark->theory_is_absent || $mark->practical_marks !== null || $mark->practical_is_absent)->count(),
                    'submission' => ExaminationMarkSubmission::where('examination_subject_id', $subject->id)
                        ->where('teacher_id', $user->id)->first(),
                ]);
            }
        }

        $lessons = RoutineLesson::query()
            ->with(['plan.organization', 'plan.department', 'section', 'period', 'endPeriod', 'groups.offering.subject', 'groups.teachers', 'groups.students'])
            ->where('day_of_week', now()->format('l'))
            ->whereHas('plan', fn ($query) => $query->where('status', 'published'))
            ->whereHas('groups', fn ($groups) => $this->teacherGroupQuery($groups, $user))
            ->get()->sortBy(fn ($lesson) => $lesson->period->starts_at)->values()
            ->map(function ($lesson) use ($user) {
                [$opensAt, $closesAt] = $this->attendanceWindow($lesson);
                $lesson->setAttribute('attendance_opens_at', $opensAt);
                $lesson->setAttribute('attendance_closes_at', $closesAt);
                $lesson->setAttribute('attendance_is_open', now()->between($opensAt, $closesAt, true));
                $lesson->setAttribute('teacher_groups', $this->teacherGroups($lesson, $user));
                return $lesson;
            });

        return view('teaching_learning.teacher-workspace.index', compact('markEntries', 'lessons'));
    }

    public function attendance(Request $request, RoutineLesson $routineLesson)
    {
        $routineLesson->load(['plan.organization', 'plan.department', 'section', 'period', 'endPeriod', 'groups.offering.subject', 'groups.teachers', 'groups.students']);
        $groups = $this->authorizeAttendance($routineLesson, $request->user());
        $students = $groups->flatMap->students->unique('id')->sortBy(fn ($student) => sprintf('%010s-%s', $student->roll_number, $student->full_name))->values();
        $session = RoutineAttendanceSession::with('attendances')->where('routine_lesson_id', $routineLesson->id)
            ->whereDate('attendance_date', now()->toDateString())->first();
        $attendance = $session?->attendances?->keyBy('student_id') ?? collect();

        return view('teaching_learning.teacher-workspace.attendance', compact('routineLesson', 'groups', 'students', 'session', 'attendance'));
    }

    public function saveAttendance(Request $request, RoutineLesson $routineLesson)
    {
        $routineLesson->load(['plan', 'period', 'endPeriod', 'groups.teachers', 'groups.students']);
        $groups = $this->authorizeAttendance($routineLesson, $request->user());
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'status' => ['required', Rule::in(['present', 'absent', 'late', 'excused'])],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);
        abort_unless($groups->flatMap->students->pluck('id')->contains((int) $data['student_id']), 422, 'This student is not in your scheduled routine group.');
        $session = $this->session($routineLesson, $request->user());
        $row = RoutineStudentAttendance::updateOrCreate(
            ['routine_attendance_session_id' => $session->id, 'student_id' => $data['student_id']],
            ['status' => $data['status'], 'remarks' => $data['remarks'] ?? null, 'marked_by' => $request->user()->id, 'marked_at' => now()]
        );

        return response()->json(['saved' => true, 'status' => $row->status, 'saved_at' => now()->format('h:i:s A')]);
    }

    public function finishAttendance(Request $request, RoutineLesson $routineLesson)
    {
        $routineLesson->load(['plan', 'period', 'endPeriod', 'groups.teachers', 'groups.students']);
        $groups = $this->authorizeAttendance($routineLesson, $request->user());
        $session = $this->session($routineLesson, $request->user());
        foreach ($groups->flatMap->students->unique('id') as $student) {
            RoutineStudentAttendance::firstOrCreate(
                ['routine_attendance_session_id' => $session->id, 'student_id' => $student->id],
                ['status' => 'present', 'marked_by' => $request->user()->id, 'marked_at' => now()]
            );
        }
        $session->update(['submitted_at' => now()]);

        return back()->with('success', 'Attendance saved for '.$groups->flatMap->students->unique('id')->count().' students.');
    }

    private function authorizeAttendance(RoutineLesson $lesson, User $user): Collection
    {
        abort_unless($lesson->plan?->status === 'published', 422, 'Attendance is available only from a published routine.');
        abort_unless($lesson->day_of_week === now()->format('l'), 422, 'This class is not scheduled today.');
        $groups = $this->teacherGroups($lesson, $user);
        abort_if($groups->isEmpty(), 403, 'This class is not assigned to you.');
        [$opensAt, $closesAt] = $this->attendanceWindow($lesson);
        abort_unless(now()->between($opensAt, $closesAt, true), 422,
            'Attendance opens '.$opensAt->format('h:i A').' and closes '.$closesAt->format('h:i A').'.');
        return $groups;
    }

    private function teacherGroups(RoutineLesson $lesson, User $user): Collection
    {
        if ($user->canAccess('teaching-learning.routine.manage')) return $lesson->groups;
        return $lesson->groups->filter(fn ($group) => (int) $group->teacher_id === (int) $user->id || $group->teachers->contains('id', $user->id))->values();
    }

    private function teacherGroupQuery($groups, User $user)
    {
        if ($user->canAccess('teaching-learning.routine.manage')) return $groups;
        return $groups->where(fn ($teachers) => $teachers->where('teacher_id', $user->id)
            ->orWhereHas('teachers', fn ($query) => $query->whereKey($user->id)));
    }

    private function attendanceWindow(RoutineLesson $lesson): array
    {
        $end = $lesson->endPeriod ?: $lesson->period;
        return [
            Carbon::parse(now()->toDateString().' '.$lesson->period->starts_at)->subMinutes(self::OPENS_BEFORE_MINUTES),
            Carbon::parse(now()->toDateString().' '.$end->ends_at)->addMinutes(self::CLOSES_AFTER_MINUTES),
        ];
    }

    private function session(RoutineLesson $lesson, User $user): RoutineAttendanceSession
    {
        return RoutineAttendanceSession::firstOrCreate(
            ['routine_lesson_id' => $lesson->id, 'attendance_date' => now()->toDateString()],
            ['opened_by' => $user->id, 'opened_at' => now()]
        );
    }
}
