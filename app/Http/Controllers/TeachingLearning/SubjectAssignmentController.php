<?php

namespace App\Http\Controllers\TeachingLearning;

use App\Http\Controllers\Controller;
use App\Models\Card\Department;
use App\Models\Card\Organization;
use App\Models\Card\Section;
use App\Models\Card\Student;
use App\Models\Card\StudentSubjectEnrollment;
use App\Models\Card\SubjectOffering;
use App\Models\TeachingLearning\AcademicYear;
use App\Services\SubjectEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubjectAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $organizations = Organization::with(['departments.sections'])->where('is_active', true)->orderBy('name')->get();
        $academicYears = AcademicYear::latest('starts_on')->latest('id')->get();
        $selectedYear = $academicYears->firstWhere('id', $request->integer('year'))
            ?? $academicYears->firstWhere('is_active', true)
            ?? $academicYears->first();
        $selectedOrganization = $organizations->firstWhere('id', $request->integer('org'));
        $selectedDepartment = $selectedOrganization?->departments->firstWhere('id', $request->integer('dept'));
        $selectedSection = $selectedDepartment?->sections->firstWhere('id', $request->integer('section'));
        $semester = $selectedDepartment?->academic_system === 'semester' ? ($request->integer('semester') ?: null) : null;
        $yearLevel = $selectedDepartment?->academic_system === 'year' ? ($request->integer('year_level') ?: null) : null;

        $offerings = collect();
        $students = collect();
        $enrollmentMap = collect();
        $electiveSummary = collect();
        $electiveEnrollmentCount = 0;

        if ($selectedDepartment && $selectedYear) {
            $offerings = SubjectOffering::with('subject')
                ->where('department_id', $selectedDepartment->id)
                ->when($semester, fn ($query) => $query->where(fn ($query) => $query->whereNull('semester')->orWhere('semester', $semester)))
                ->when($yearLevel, fn ($query) => $query->where(fn ($query) => $query->whereNull('year_level')->orWhere('year_level', $yearLevel)))
                ->when($selectedSection, fn ($query) => $query->where(fn ($query) => $query
                    ->whereNull('group_name')
                    ->when($selectedSection->group_name, fn ($query, $group) => $query->orWhere('group_name', $group))))
                ->orderBy('is_elective')->orderBy('elective_group')->get();

            $students = Student::query()
                ->tap(fn ($query) => auth()->user()->applyStudentScope($query))
                ->where('member_type', 'student')
                ->where('organization', $selectedOrganization->slug)
                ->where('stream', $selectedDepartment->name)
                ->when($semester, fn ($query) => $query->where('semester', $semester))
                ->when($yearLevel, fn ($query) => $query->where('year_level', $yearLevel))
                ->when($selectedSection, fn ($query) => $query->where('section_id', $selectedSection->id))
                ->orderByRaw('roll_number IS NULL')->orderBy('roll_number')->orderBy('first_name')
                ->get(['id', 'roll_number', 'first_name', 'middle_name', 'last_name', 'section', 'section_id', 'semester', 'year_level']);

            $enrollmentMap = StudentSubjectEnrollment::whereIn('student_id', $students->pluck('id'))
                ->where('academic_year', $selectedYear->name)
                ->whereNotNull('subject_offering_id')
                ->get()
                ->groupBy('student_id')
                ->map(fn ($rows) => $rows->pluck('subject_offering_id')->map(fn ($id) => (int) $id)->values());

            $sectionNames = $selectedDepartment->sections->pluck('name', 'id');
            $electiveEnrollments = StudentSubjectEnrollment::with([
                'student:id,first_name,middle_name,last_name,roll_number,section,section_id',
                'offering.subject',
            ])
                ->whereIn('student_id', $students->pluck('id'))
                ->where('academic_year', $selectedYear->name)
                ->where('assignment_source', 'manual')
                ->whereHas('offering', fn ($query) => $query
                    ->where('department_id', $selectedDepartment->id)
                    ->where('is_elective', true))
                ->get();
            $electiveEnrollmentCount = $electiveEnrollments->count();
            $electiveSummary = $electiveEnrollments
                ->groupBy(function ($enrollment) use ($sectionNames) {
                    $student = $enrollment->student;
                    return $student?->section_id
                        ? ($sectionNames->get($student->section_id) ?? $student->section ?? 'Unassigned')
                        : ($student?->section ?: 'Unassigned');
                })
                ->map(fn ($sectionEnrollments) => $sectionEnrollments
                    ->groupBy('subject_offering_id')
                    ->map(fn ($subjectEnrollments) => [
                        'offering' => $subjectEnrollments->first()->offering,
                        'students' => $subjectEnrollments->pluck('student')->filter()
                            ->sortBy(fn ($student) => sprintf('%s|%s', $student->roll_number, $student->full_name))
                            ->values(),
                    ])
                    ->values())
                ->sortKeys();
        }

        return view('teaching_learning.subject-assignments.index', compact(
            'organizations', 'academicYears', 'selectedYear', 'selectedOrganization', 'selectedDepartment',
            'selectedSection', 'semester', 'yearLevel', 'offerings', 'students', 'enrollmentMap',
            'electiveSummary', 'electiveEnrollmentCount'
        ));
    }

    public function syncFixed(Request $request, SubjectEnrollmentService $enrollments)
    {
        $data = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
        ]);
        $year = AcademicYear::findOrFail($data['academic_year_id']);
        if ($year->is_locked) throw ValidationException::withMessages(['academic_year_id' => 'Unlock the academic year before synchronizing subjects.']);
        $department = Department::with('organization')->findOrFail($data['department_id']);
        $count = $enrollments->syncDepartment($department, $year);
        $studentCount = StudentSubjectEnrollment::query()
            ->where('academic_year', $year->name)
            ->where('assignment_source', 'automatic')
            ->whereHas('offering', fn ($query) => $query->where('department_id', $department->id))
            ->distinct('student_id')
            ->count('student_id');
        $subjectCount = SubjectOffering::where('department_id', $department->id)
            ->where('is_elective', false)
            ->count();

        return back()->with('success', "{$studentCount} student(s) synchronized with {$subjectCount} compulsory subject(s). {$count} individual subject enrollment record(s) are active.");
    }

    public function updateElective(Request $request, SubjectEnrollmentService $enrollments)
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'subject_offering_id' => ['required', 'integer', 'exists:subject_offerings,id'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'scope_student_ids' => ['nullable', 'array'],
            'scope_student_ids.*' => ['integer', 'exists:students,id'],
            'mode' => ['required', 'in:assign,remove,replace'],
        ]);
        $year = AcademicYear::findOrFail($data['academic_year_id']);
        if ($year->is_locked) throw ValidationException::withMessages(['academic_year_id' => 'Unlock the academic year before changing elective assignments.']);
        $offering = SubjectOffering::with('department.organization')->findOrFail($data['subject_offering_id']);
        if (! $offering->is_elective) throw ValidationException::withMessages(['subject_offering_id' => 'Compulsory subjects are assigned automatically.']);

        $studentIds = collect($data['student_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        if ($data['mode'] !== 'replace' && $studentIds->isEmpty()) {
            throw ValidationException::withMessages(['student_ids' => 'Select at least one student.']);
        }
        $students = Student::query()
            ->tap(fn ($query) => auth()->user()->applyStudentScope($query))
            ->whereIn('id', $studentIds)
            ->get();
        if ($students->count() !== $studentIds->count()) {
            throw ValidationException::withMessages(['student_ids' => 'One or more selected students are outside your access scope.']);
        }
        if ($students->contains(fn ($student) => ! $enrollments->studentMatchesOffering($student, $offering))) {
            throw ValidationException::withMessages(['student_ids' => 'Every selected student must match the elective department, semester/year, and class group.']);
        }

        $scopeIds = collect($data['scope_student_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        if ($data['mode'] === 'replace') {
            if ($scopeIds->isEmpty() || $studentIds->diff($scopeIds)->isNotEmpty()) {
                throw ValidationException::withMessages(['scope_student_ids' => 'The edited students must belong to the selected section.']);
            }
            $scopeStudents = Student::query()->tap(fn ($query) => auth()->user()->applyStudentScope($query))->whereIn('id', $scopeIds)->get();
            if ($scopeStudents->count() !== $scopeIds->count() || $scopeStudents->contains(fn ($student) => ! $enrollments->studentMatchesOffering($student, $offering))) {
                throw ValidationException::withMessages(['scope_student_ids' => 'The edited section is outside your access scope.']);
            }
        }

        DB::transaction(function () use ($data, $year, $offering, $students, $scopeIds) {
            if ($data['mode'] === 'replace') {
                StudentSubjectEnrollment::whereIn('student_id', $scopeIds)
                    ->where('subject_offering_id', $offering->id)
                    ->where('academic_year', $year->name)
                    ->where('assignment_source', 'manual')
                    ->delete();
            }

            if (in_array($data['mode'], ['assign', 'replace'], true) && $offering->elective_group) {
                StudentSubjectEnrollment::whereIn('student_id', $students->pluck('id'))
                    ->where('academic_year', $year->name)
                    ->where('assignment_source', 'manual')
                    ->whereHas('offering', fn ($query) => $query
                        ->where('department_id', $offering->department_id)
                        ->where('is_elective', true)
                        ->where('elective_group', $offering->elective_group))
                    ->delete();
            }

            foreach ($students as $student) {
                if ($data['mode'] === 'remove') {
                    StudentSubjectEnrollment::where('student_id', $student->id)
                        ->where('subject_offering_id', $offering->id)
                        ->where('academic_year', $year->name)
                        ->where('assignment_source', 'manual')
                        ->delete();
                    continue;
                }

                StudentSubjectEnrollment::updateOrCreate(
                    ['student_id' => $student->id, 'subject_id' => $offering->subject_id, 'academic_year' => $year->name],
                    ['subject_offering_id' => $offering->id, 'assignment_source' => 'manual']
                );
            }
        });

        if (in_array($data['mode'], ['assign', 'replace'], true)) {
            $enrollments->syncElectiveIntoExams($offering, $year);
        }

        return back()->with('success', match ($data['mode']) {
            'assign' => "Elective assigned to {$students->count()} student(s).",
            'remove' => "Elective removed from {$students->count()} student(s).",
            default => "Elective assignment updated for the section. {$students->count()} student(s) selected.",
        });
    }

    // Student x elective checkbox grid: each student can be ticked for any number of the
    // electives shown (e.g. must take 2 of 3 — student X takes A+C, student Y takes A+B).
    // Unlike updateElective(), this never enforces "one offering per elective_group" —
    // holding several electives from the grid at once is the whole point here.
    public function updateElectivesPerStudent(Request $request, SubjectEnrollmentService $enrollments)
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'offering_ids' => ['required', 'array', 'min:1'],
            'offering_ids.*' => ['integer', 'exists:subject_offerings,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'assignments' => ['nullable', 'array'],
            'assignments.*' => ['array'],
            'assignments.*.*' => ['integer'],
        ]);
        $year = AcademicYear::findOrFail($data['academic_year_id']);
        if ($year->is_locked) throw ValidationException::withMessages(['academic_year_id' => 'Unlock the academic year before changing elective assignments.']);
        $department = Department::findOrFail($data['department_id']);

        $offeringIds = collect($data['offering_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $offerings = SubjectOffering::with('subject')->whereIn('id', $offeringIds)
            ->where('department_id', $department->id)->where('is_elective', true)->get()->keyBy('id');
        if ($offerings->count() !== $offeringIds->count()) {
            throw ValidationException::withMessages(['offering_ids' => 'One or more electives are not part of this department.']);
        }

        $studentIds = collect($data['student_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $students = Student::query()
            ->tap(fn ($query) => auth()->user()->applyStudentScope($query))
            ->whereIn('id', $studentIds)->get()->keyBy('id');
        if ($students->count() !== $studentIds->count()) {
            throw ValidationException::withMessages(['student_ids' => 'One or more selected students are outside your access scope.']);
        }

        $choices = collect($data['assignments'] ?? [])->mapWithKeys(fn ($ids, $studentId) => [
            (int) $studentId => collect($ids)->map(fn ($id) => (int) $id)->unique()->values(),
        ]);
        foreach ($students as $student) {
            foreach ($choices->get($student->id, collect()) as $offeringId) {
                $offering = $offerings->get($offeringId);
                if (! $offering) throw ValidationException::withMessages(['assignments' => 'One or more ticked electives are not part of this grid.']);
                if (! $enrollments->studentMatchesOffering($student, $offering)) {
                    throw ValidationException::withMessages(['assignments' => "{$student->full_name} does not match {$offering->subject->name}'s department, semester/year, and class group."]);
                }
            }
        }

        $changed = 0;
        DB::transaction(function () use ($year, $students, $offerings, $choices, &$changed) {
            foreach ($students as $student) {
                $chosen = $choices->get($student->id, collect());
                StudentSubjectEnrollment::where('student_id', $student->id)
                    ->where('academic_year', $year->name)
                    ->where('assignment_source', 'manual')
                    ->whereIn('subject_offering_id', $offerings->keys())
                    ->whereNotIn('subject_offering_id', $chosen)
                    ->delete();
                foreach ($chosen as $offeringId) {
                    $offering = $offerings->get($offeringId);
                    StudentSubjectEnrollment::updateOrCreate(
                        ['student_id' => $student->id, 'subject_id' => $offering->subject_id, 'academic_year' => $year->name],
                        ['subject_offering_id' => $offering->id, 'assignment_source' => 'manual']
                    );
                }
                $changed++;
            }
        });

        foreach ($offerings as $offering) {
            $enrollments->syncElectiveIntoExams($offering, $year);
        }

        return back()->with('success', "Elective choices saved for {$changed} student(s).");
    }
}
