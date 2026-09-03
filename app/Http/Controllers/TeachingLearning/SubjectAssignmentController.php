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

        if ($selectedDepartment && $selectedYear) {
            $offerings = SubjectOffering::with('subject')
                ->where('department_id', $selectedDepartment->id)
                ->where(fn ($query) => $query->whereNull('semester')->when($semester, fn ($query) => $query->orWhere('semester', $semester)))
                ->where(fn ($query) => $query->whereNull('year_level')->when($yearLevel, fn ($query) => $query->orWhere('year_level', $yearLevel)))
                ->where(fn ($query) => $query->whereNull('group_name')->when($selectedSection?->group_name, fn ($query, $group) => $query->orWhere('group_name', $group)))
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
        }

        return view('teaching_learning.subject-assignments.index', compact(
            'organizations', 'academicYears', 'selectedYear', 'selectedOrganization', 'selectedDepartment',
            'selectedSection', 'semester', 'yearLevel', 'offerings', 'students', 'enrollmentMap'
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

        return back()->with('success', "Compulsory subjects synchronized. {$count} student-subject assignment(s) are active.");
    }

    public function updateElective(Request $request, SubjectEnrollmentService $enrollments)
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'subject_offering_id' => ['required', 'integer', 'exists:subject_offerings,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'mode' => ['required', 'in:assign,remove'],
        ]);
        $year = AcademicYear::findOrFail($data['academic_year_id']);
        if ($year->is_locked) throw ValidationException::withMessages(['academic_year_id' => 'Unlock the academic year before changing elective assignments.']);
        $offering = SubjectOffering::with('department.organization')->findOrFail($data['subject_offering_id']);
        if (! $offering->is_elective) throw ValidationException::withMessages(['subject_offering_id' => 'Compulsory subjects are assigned automatically.']);

        $students = Student::query()
            ->tap(fn ($query) => auth()->user()->applyStudentScope($query))
            ->whereIn('id', $data['student_ids'])
            ->get();
        if ($students->count() !== count(array_unique($data['student_ids']))) {
            throw ValidationException::withMessages(['student_ids' => 'One or more selected students are outside your access scope.']);
        }
        if ($students->contains(fn ($student) => ! $enrollments->studentMatchesOffering($student, $offering))) {
            throw ValidationException::withMessages(['student_ids' => 'Every selected student must match the elective department, semester/year, and class group.']);
        }

        DB::transaction(function () use ($data, $year, $offering, $students) {
            if ($data['mode'] === 'assign' && $offering->elective_group) {
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

        return back()->with('success', $data['mode'] === 'assign'
            ? "Elective assigned to {$students->count()} student(s)."
            : "Elective removed from {$students->count()} student(s).");
    }
}
