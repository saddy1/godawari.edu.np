<?php

namespace App\Http\Controllers\Examination;

use App\Http\Controllers\Controller;
use App\Models\Card\Section;
use App\Models\Card\Student;
use App\Models\Card\StudentSubjectEnrollment;
use App\Models\Examination\Examination;
use App\Models\Examination\ExaminationAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExamAttendanceController extends Controller
{
    public function index(Request $request, Examination $examination)
    {
        $examination->load(['organization', 'academicYear', 'sections.department']);
        $date = $this->resolveDate($request);
        $today = Carbon::today();
        abort_if($date->gt($today), 422, 'You cannot take exam attendance for a future date.');

        $subjectsOnDate = $this->subjectsOnDate($examination, $date);
        $departments = $subjectsOnDate->pluck('offering.department')->filter()->unique('id')->sortBy('name')->values();
        $selectedDepartment = $departments->firstWhere('id', $request->integer('department_id'));

        $sections = $selectedDepartment
            ? $examination->sections->where('department_id', $selectedDepartment->id)->sortBy('name')->values()
            : collect();
        $selectedSection = $sections->firstWhere('id', $request->integer('section_id'));

        $roster = collect();
        if ($selectedDepartment && $selectedSection) {
            $roster = $this->buildRoster($examination, $subjectsOnDate, $selectedDepartment, $selectedSection);
            $this->attachExistingAttendance($roster);
        }

        // Find a student directly, across every faculty/section with an exam
        // on this date, without first drilling through faculty -> section —
        // each hit links straight to that student's own section roster.
        $query = trim((string) $request->query('q', ''));
        $searchResults = collect();
        if ($query !== '') {
            foreach ($departments as $dept) {
                foreach ($examination->sections->where('department_id', $dept->id) as $sec) {
                    foreach ($this->buildRoster($examination, $subjectsOnDate, $dept, $sec) as $row) {
                        $haystack = mb_strtolower($row->student->full_name.' '.$row->student->roll_number);
                        if (str_contains($haystack, mb_strtolower($query))) {
                            $searchResults->push((object) ['student' => $row->student, 'department' => $dept, 'section' => $sec, 'subject_name' => $row->subject_name]);
                        }
                    }
                }
            }
        }

        if ($request->routeIs('admin.examinations.attendance.search')) {
            return response()->json([
                'html' => view('examinations.attendance._search-results', compact('examination', 'date', 'query', 'searchResults'))->render(),
            ])->header('Cache-Control', 'private, no-store, max-age=0');
        }

        return view('examinations.attendance.index', [
            'examination' => $examination,
            'date' => $date,
            'todayBs' => $this->bsDate($today),
            'departments' => $departments,
            'selectedDepartment' => $selectedDepartment,
            'sections' => $sections,
            'selectedSection' => $selectedSection,
            'roster' => $roster,
            'query' => $query,
            'searchResults' => $searchResults,
        ]);
    }

    public function store(Request $request, Examination $examination)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'department_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
            'absent_student_ids' => ['nullable', 'array'],
            'absent_student_ids.*' => ['integer'],
            'reasons' => ['nullable', 'array'],
            'reasons.*' => ['nullable', 'string', 'max:255'],
        ]);

        $date = Carbon::parse($data['date']);
        abort_if($date->gt(Carbon::today()), 422, 'You cannot save exam attendance for a future date.');
        $department = \App\Models\Card\Department::find($data['department_id']);
        $section = Section::find($data['section_id']);
        abort_unless($department && $section, 404);

        $examination->loadMissing('academicYear');
        $subjectsOnDate = $this->subjectsOnDate($examination, $date);
        $roster = $this->buildRoster($examination, $subjectsOnDate, $department, $section);
        abort_if($roster->isEmpty(), 422, 'No students have an exam scheduled for this faculty, section and date.');

        $absentIds = collect($data['absent_student_ids'] ?? [])->map(fn ($id) => (int) $id)->unique();
        $reasons = $data['reasons'] ?? [];
        $markedBy = auth()->id();
        $now = now();

        DB::transaction(function () use ($roster, $absentIds, $reasons, $examination, $date, $markedBy, $now) {
            foreach ($roster as $row) {
                $isAbsent = $absentIds->contains($row->student->id);
                ExaminationAttendance::updateOrCreate(
                    ['examination_subject_id' => $row->examination_subject_id, 'student_id' => $row->student->id],
                    [
                        'examination_id' => $examination->id,
                        'exam_date' => $date,
                        'status' => $isAbsent ? 'absent' : 'present',
                        'reason' => $isAbsent ? ($reasons[$row->student->id] ?? null) : null,
                        'marked_by' => $markedBy,
                        'marked_at' => $now,
                    ]
                );
            }
        });

        return back()->with('success', "Attendance saved for {$roster->count()} student(s) — {$absentIds->count()} marked absent.");
    }

    // Every subject (across every department/offering) that has its theory or
    // practical sitting on this date, for this exam — this is what "which
    // faculties have an exam today" actually resolves against, since scheduling
    // lives per-offering (examination_subjects), not on the exam's own broad
    // departments()/sections() pivots.
    private function subjectsOnDate(Examination $examination, Carbon $date): Collection
    {
        return $examination->subjects()
            ->with(['offering.department', 'offering.subject'])
            ->where(fn ($query) => $query->whereDate('exam_date', $date)->orWhereDate('practical_exam_date', $date))
            ->get();
    }

    // One row per student who actually has an exam today for this department +
    // section — matched through their own subject enrollment, not just "every
    // student in the section," so an elective split on the same date still
    // lands each student against the specific subject they're actually sitting.
    private function buildRoster(Examination $examination, Collection $subjectsOnDate, $department, Section $section): Collection
    {
        $deptSubjects = $subjectsOnDate->filter(fn ($es) => $es->offering->department_id === $department->id);
        if ($deptSubjects->isEmpty()) {
            return collect();
        }

        $students = Student::where('member_type', 'student')
            ->where(fn ($query) => $query->where('section_id', $section->id)
                ->orWhere(fn ($legacy) => $legacy->whereNull('section_id')->where('section', $section->name)))
            ->orderByRaw('roll_number IS NULL')->orderBy('roll_number')->orderBy('first_name')
            ->get();

        if ($students->isEmpty()) {
            return collect();
        }

        $enrollments = StudentSubjectEnrollment::whereIn('student_id', $students->pluck('id'))
            ->where('academic_year', $examination->academicYear->name)
            ->whereIn('subject_offering_id', $deptSubjects->pluck('subject_offering_id'))
            ->get()
            ->keyBy('student_id');

        return $students->map(function (Student $student) use ($enrollments, $deptSubjects) {
            $enrollment = $enrollments->get($student->id);
            if (! $enrollment) {
                return null;
            }
            $examSubject = $deptSubjects->firstWhere('subject_offering_id', $enrollment->subject_offering_id);
            if (! $examSubject) {
                return null;
            }

            return (object) [
                'student' => $student,
                'examination_subject_id' => $examSubject->id,
                'subject_name' => $examSubject->offering->subject->name,
                'status' => 'present',
                'reason' => null,
            ];
        })->filter()->values();
    }

    private function attachExistingAttendance(Collection $roster): void
    {
        if ($roster->isEmpty()) {
            return;
        }

        $existing = ExaminationAttendance::whereIn('examination_subject_id', $roster->pluck('examination_subject_id')->unique())
            ->whereIn('student_id', $roster->pluck('student.id'))
            ->get()
            ->keyBy(fn ($row) => $row->examination_subject_id.':'.$row->student_id);

        foreach ($roster as $row) {
            $saved = $existing->get($row->examination_subject_id.':'.$row->student->id);
            if ($saved) {
                $row->status = $saved->status;
                $row->reason = $saved->reason;
            }
        }
    }

    private function resolveDate(Request $request): Carbon
    {
        return $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();
    }

    private function bsDate(Carbon $date): ?string
    {
        $converter = new \App\Http\Controllers\Hajiri\NepaliCalendarController();
        $bs = $converter->ad_2_bs($date->year, $date->month, $date->day);

        return $bs ? sprintf('%04d-%02d-%02d', $bs['year'], $bs['month'], $bs['date']) : null;
    }
}
