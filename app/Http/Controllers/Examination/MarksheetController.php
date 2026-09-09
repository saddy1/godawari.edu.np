<?php

namespace App\Http\Controllers\Examination;

use App\Http\Controllers\Controller;
use App\Models\Card\Department;
use App\Models\Card\Student;
use App\Models\Examination\Examination;
use App\Models\Examination\ExaminationMark;
use App\Models\Examination\ExaminationSymbolNumber;
use App\Services\ExamRosterService;
use App\Services\GradeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class MarksheetController extends Controller
{
    public function index(Request $request, Examination $examination, ExamRosterService $roster)
    {
        $request->validate(['q' => 'nullable|string|max:150', 'page' => 'nullable|integer|min:1']);
        $examination->loadMissing('organization');
        $symbols = ExaminationSymbolNumber::where('examination_id', $examination->id)->pluck('symbol_no', 'student_id');
        $allStudents = $roster->studentsForExam($examination);
        $filterOptions = $this->studentsWithClass($examination, $allStudents)->map(fn ($student) => [
            'school_class' => $student->school_class,
            'faculty' => $student->stream,
            'section' => $student->academicSection?->name ?? $student->section,
        ])->unique(fn ($row) => json_encode($row))->values();
        $students = $this->filterStudents($request, $examination, $allStudents, $symbols);
        $students = new LengthAwarePaginator($students->forPage($request->integer('page', 1), 30), $students->count(), 30, $request->integer('page', 1), ['path' => $request->url(), 'query' => $request->query()]);
        return view('examinations.marksheets.index', compact('examination', 'students', 'symbols', 'filterOptions'));
    }

    private function studentsWithClass(Examination $examination, Collection $students): Collection
    {
        $departments = Department::where('organization_id', $examination->organization_id)->get()->keyBy('name');

        return $students->each(function ($student) use ($departments) {
            $student->school_class = $student->academicSection?->department?->school_class ?? $departments->get($student->stream)?->school_class;
        });
    }

    public function print(Request $request, Examination $examination, ExamRosterService $roster, GradeService $grades)
    {
        $request->validate(['student_id' => 'nullable|integer|min:1']);
        return view('examinations.marksheets.print', [
            'examination' => $examination,
            'roster' => $this->rosterWithGrades($request, $examination, $roster, $grades, $request->integer('student_id') ?: null),
        ]);
    }

    public function downloadAll(Request $request, Examination $examination, ExamRosterService $roster, GradeService $grades)
    {
        $pdf = Pdf::loadView('examinations.marksheets.pdf', [
            'examination' => $examination,
            'roster' => $this->rosterWithGrades($request, $examination, $roster, $grades),
        ])->setPaper('a4', 'portrait');

        return $pdf->download(str($examination->name)->slug('_').'_marksheets.pdf');
    }

    public function downloadOne(Examination $examination, Student $student, ExamRosterService $roster, GradeService $grades)
    {
        $this->eagerLoadBranding($examination);
        $symbolNo = ExaminationSymbolNumber::where('examination_id', $examination->id)->where('student_id', $student->id)->value('symbol_no');
        abort_if(! $symbolNo, 404, 'Assign a symbol number to this student first.');
        $row = $this->buildRow($examination, $student, $symbolNo, $roster, $grades);

        $pdf = Pdf::loadView('examinations.marksheets.pdf', [
            'examination' => $examination,
            'roster' => collect([$row]),
        ])->setPaper('a4', 'portrait');

        return $pdf->download(str($student->full_name)->slug('_').'_marksheet.pdf');
    }

    private function filterStudents(Request $request, Examination $examination, Collection $students, Collection $symbols): Collection
    {
        $request->validate(['q' => 'nullable|string|max:150', 'school_class' => 'nullable|in:11,12', 'faculty' => 'nullable|string|max:150', 'section' => 'nullable|string|max:150']);
        $departments = \App\Models\Card\Department::where('organization_id', $examination->organization_id)->get()->keyBy('name');
        $query = mb_strtolower(trim((string) $request->input('q', '')));
        return $students->filter(function ($student) use ($request, $departments, $symbols, $query) {
            $section = $student->academicSection?->name ?? $student->section;
            $class = $student->academicSection?->department?->school_class ?? $departments->get($student->stream)?->school_class;
            return (! $request->filled('school_class') || $class === $request->integer('school_class'))
                && (! $request->filled('faculty') || $student->stream === $request->input('faculty'))
                && (! $request->filled('section') || $section === $request->input('section'))
                && ($query === '' || str_contains(mb_strtolower(implode(' ', [$student->full_name, $student->stream, $section, $symbols->get($student->id)])), $query));
        })->values();
    }

    private function eagerLoadBranding(Examination $examination): void
    {
        $examination->loadMissing(['organization.logoAsset', 'organization.signatureAsset', 'organization.stampAsset', 'academicYear']);
    }

    private function rosterWithGrades(Request $request, Examination $examination, ExamRosterService $roster, GradeService $grades, ?int $studentId = null): Collection
    {
        $this->eagerLoadBranding($examination);
        $students = $roster->studentsForExam($examination);
        if ($studentId !== null) {
            abort_unless($students->contains('id', $studentId), 404);
            $students = $students->where('id', $studentId);
        }
        $symbolNumbers = ExaminationSymbolNumber::where('examination_id', $examination->id)->pluck('symbol_no', 'student_id');

        return $this->filterStudents($request, $examination, $students, $symbolNumbers)->filter(fn ($student) => $symbolNumbers->has($student->id))
            ->map(fn ($student) => $this->buildRow($examination, $student, $symbolNumbers->get($student->id), $roster, $grades))
            ->values();
    }

    private function buildRow(Examination $examination, Student $student, int $symbolNo, ExamRosterService $roster, GradeService $grades): array
    {
        $subjects = $roster->subjectsForStudent($examination, $student);
        $marks = ExaminationMark::whereIn('examination_subject_id', $subjects->pluck('id'))
            ->where('student_id', $student->id)->get()->keyBy('examination_subject_id');

        $subjectRows = $subjects->flatMap(function ($subject) use ($marks, $grades, $examination) {
            $mark = $marks->get($subject->id);
            $model = $subject->offering->subject;
            $components = ['theory' => ['TH', $model->code, (float) $subject->theory_full_marks]];
            if ($examination->practical_enabled && (float) $subject->practical_full_marks > 0) {
                $components['practical'] = ['PR', $model->practical_code ?: $model->code, (float) $subject->practical_full_marks];
            }
            $totalFull = array_sum(array_column($components, 2));
            $rows = [];
            foreach ($components as $component => [$label, $code, $full]) {
                if ($full <= 0) continue;
                $absent = (bool) $mark?->{$component.'_is_absent'};
                $obtained = $mark?->{$component.'_marks'};
                $graded = ! $absent && $obtained !== null;
                $grade = $absent ? ['grade' => 'Abs', 'point' => 0.0]
                    : ($graded ? $grades->gradeFor(round((float) $obtained / $full * 100, 2)) : null);
                $rows[] = [
                    'code' => $code, 'name' => $model->name, 'label' => $label,
                    // Only total subject credits are stored; apportion without doubling them.
                    'credit_hour' => (float) $model->credit_hours * $full / $totalFull,
                    'grade' => $grade['grade'] ?? null, 'point' => $grade['point'] ?? null,
                    'remarks' => $mark?->remarks ?: ($absent ? 'Absent' : ($graded ? null : 'Incomplete')),
                    'countable' => $absent || $graded,
                ];
            }
            return $rows;
        })->values();

        $mainSubjects = $subjectRows;
        $extraSubjects = collect();
        $gpa = $mainSubjects->isNotEmpty() && $mainSubjects->every(fn ($row) => $row['countable'])
            ? $grades->gpaFor($mainSubjects) : null;

        return [
            'student' => $student,
            'symbol_no' => $symbolNo,
            'department' => $subjects->first()?->offering?->department,
            'subjectRows' => $mainSubjects,
            'extraSubjects' => $extraSubjects,
            'gpa' => $gpa,
        ];
    }
}
