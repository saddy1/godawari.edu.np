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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeacherWorkspaceController extends Controller
{
    private const OPENS_BEFORE_MINUTES = 10;

    public function index(Request $request, ExamTeacherAssignmentService $examAssignments)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isTeacher() || $user->canAccess([
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
            ->get()->sortBy(fn ($lesson) => $lesson->period->starts_at)->values();

        $attendanceCounts = RoutineStudentAttendance::query()
            ->join('routine_attendance_sessions', 'routine_attendance_sessions.id', '=', 'routine_student_attendances.routine_attendance_session_id')
            ->whereIn('routine_attendance_sessions.routine_lesson_id', $lessons->pluck('id'))
            ->whereDate('routine_attendance_sessions.attendance_date', now()->toDateString())
            ->selectRaw('routine_attendance_sessions.routine_lesson_id, routine_student_attendances.status, count(*) as total')
            ->groupBy('routine_attendance_sessions.routine_lesson_id', 'routine_student_attendances.status')
            ->get()
            ->groupBy('routine_lesson_id');

        $lessons = $lessons->map(function ($lesson) use ($user, $attendanceCounts) {
            [$opensAt, $closesAt] = $this->attendanceWindow($lesson);
            $counts = $attendanceCounts->get($lesson->id, collect());
            $lesson->setAttribute('attendance_opens_at', $opensAt);
            $lesson->setAttribute('attendance_closes_at', $closesAt);
            $lesson->setAttribute('attendance_is_open', now()->gte($opensAt) && now()->lt($closesAt));
            $lesson->setAttribute('period_starts_at', Carbon::parse(now()->toDateString().' '.$lesson->period->starts_at));
            $lesson->setAttribute('teacher_groups', $this->teacherGroups($lesson, $user));
            $lesson->setAttribute('present_count', (int) $counts->whereIn('status', ['present', 'late'])->sum('total'));
            $lesson->setAttribute('absent_count', (int) $counts->where('status', 'absent')->sum('total'));
            return $lesson;
        });

        // A class only gets its 10-minute "open early" head start if the teacher isn't
        // still genuinely inside another class's real scheduled time — otherwise two
        // back-to-back periods can both show as "open" for that 10-minute overlap.
        $inSessionNow = $lessons->contains(fn ($lesson) => now()->gte($lesson->period_starts_at) && now()->lt($lesson->attendance_closes_at));
        if ($inSessionNow) {
            $lessons = $lessons->map(function ($lesson) {
                if ($lesson->attendance_is_open && now()->lt($lesson->period_starts_at)) {
                    $lesson->setAttribute('attendance_is_open', false);
                    $lesson->setAttribute('attendance_opens_at', $lesson->period_starts_at);
                }
                return $lesson;
            });
        }

        return view('teaching_learning.teacher-workspace.index', compact('markEntries', 'lessons'));
    }

    public function attendance(Request $request, RoutineLesson $routineLesson)
    {
        $routineLesson->load(['plan.organization', 'plan.department', 'section', 'period', 'endPeriod', 'groups.offering.subject', 'groups.teachers', 'groups.students']);
        $groups = $this->authorizeAttendance($routineLesson, $request->user(), false);
        $students = $groups->flatMap->students->unique('id')->sortBy(fn ($student) => trim($student->full_name), SORT_NATURAL | SORT_FLAG_CASE)->values();
        $session = RoutineAttendanceSession::with('attendances')->where('routine_lesson_id', $routineLesson->id)
            ->whereDate('attendance_date', now()->toDateString())->first();
        $attendance = $session?->attendances?->keyBy('student_id') ?? collect();

        [$opensAt, $closesAt] = $this->attendanceWindow($routineLesson);
        $editable = now()->gte($opensAt) && now()->lt($closesAt);
        return view('teaching_learning.teacher-workspace.attendance', compact('routineLesson', 'groups', 'students', 'session', 'attendance', 'editable', 'closesAt'));
    }

    public function saveAttendance(Request $request, RoutineLesson $routineLesson)
    {
        $routineLesson->load(['plan', 'period', 'endPeriod', 'groups.teachers', 'groups.students']);
        $groups = $this->authorizeAttendance($routineLesson, $request->user());
        $data = $request->validate([
            'student_id' => ['required_without:all', 'nullable', 'integer'],
            'all' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(['present', 'absent'])],
        ]);
        $students = $groups->flatMap->students->unique('id');
        $all = $request->boolean('all');
        abort_unless($all || $students->pluck('id')->contains((int) ($data['student_id'] ?? 0)), 422, 'This student is not in your scheduled routine group.');
        DB::transaction(function () use ($routineLesson, $request, $students, $all, $data) {
            $session = $this->session($routineLesson, $request->user());
            RoutineAttendanceSession::whereKey($session->id)->lockForUpdate()->first();
            $this->authorizeAttendance($routineLesson, $request->user());
            foreach ($students as $student) {
                $isTarget = $all || (int) $student->id === (int) ($data['student_id'] ?? 0);
                if (! $isTarget) {
                    continue;
                }
                $key = ['routine_attendance_session_id' => $session->id, 'student_id' => $student->id];
                RoutineStudentAttendance::updateOrCreate($key, [
                    'status' => $data['status'],
                    'marked_by' => $request->user()->id,
                    'marked_at' => now(),
                ]);
            }
            $session->update(['submitted_at' => now()]);
        });

        return response()->json(['saved' => true, 'saved_at' => now()->format('h:i:s A')]);
    }

    private function authorizeAttendance(RoutineLesson $lesson, User $user, bool $editing = true): Collection
    {
        abort_unless($lesson->plan?->status === 'published', 422, 'Attendance is available only from a published routine.');
        abort_unless($lesson->day_of_week === now()->format('l'), 422, 'This class is not scheduled today.');
        $groups = $this->teacherGroups($lesson, $user);
        abort_if($groups->isEmpty(), 403, 'This class is not assigned to you.');
        [$opensAt, $closesAt] = $this->attendanceWindow($lesson);
        abort_unless(!$editing || (now()->gte($opensAt) && now()->lt($closesAt)), 422,
            'Attendance opens '.$opensAt->format('h:i A').' and closes '.$closesAt->format('h:i A').'.');
        return $groups;
    }

    // Always scoped to literal teacher assignment — being an admin/super-admin
    // does not imply "my classes today" should include every class in the school.
    private function teacherGroups(RoutineLesson $lesson, User $user): Collection
    {
        return $lesson->groups->filter(fn ($group) => (int) $group->teacher_id === (int) $user->id || $group->teachers->contains('id', $user->id))->values();
    }

    private function teacherGroupQuery($groups, User $user)
    {
        return $groups->where(fn ($teachers) => $teachers->where('teacher_id', $user->id)
            ->orWhereHas('teachers', fn ($query) => $query->whereKey($user->id)));
    }

    private function attendanceWindow(RoutineLesson $lesson): array
    {
        $end = $lesson->endPeriod ?: $lesson->period;
        return [
            Carbon::parse(now()->toDateString().' '.$lesson->period->starts_at)->subMinutes(self::OPENS_BEFORE_MINUTES),
            Carbon::parse(now()->toDateString().' '.$end->ends_at),
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
