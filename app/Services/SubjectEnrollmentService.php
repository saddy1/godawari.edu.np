<?php

namespace App\Services;

use App\Models\Card\Department;
use App\Models\Card\Student;
use App\Models\Card\StudentSubjectEnrollment;
use App\Models\Card\SubjectOffering;
use App\Models\TeachingLearning\AcademicYear;
use Illuminate\Database\Eloquent\Builder;

class SubjectEnrollmentService
{
    public function currentWritableAcademicYear(): ?AcademicYear
    {
        return AcademicYear::where('is_locked', false)
            ->orderByDesc('is_active')
            ->latest('starts_on')
            ->latest('id')
            ->first();
    }

    public function syncOffering(SubjectOffering $offering, ?AcademicYear $academicYear = null): int
    {
        $academicYear ??= $this->currentWritableAcademicYear();
        if (! $academicYear) return 0;

        StudentSubjectEnrollment::where('subject_offering_id', $offering->id)
            ->where('academic_year', $academicYear->name)
            ->where('assignment_source', 'automatic')
            ->delete();

        if ($offering->is_elective) return 0;

        $created = 0;
        $this->eligibleStudentsQuery($offering)->select('students.id')->chunkById(200, function ($students) use ($offering, $academicYear, &$created) {
            foreach ($students as $student) {
                StudentSubjectEnrollment::updateOrCreate(
                    ['student_id' => $student->id, 'subject_id' => $offering->subject_id, 'academic_year' => $academicYear->name],
                    ['subject_offering_id' => $offering->id, 'assignment_source' => 'automatic']
                );
                $created++;
            }
        });

        return $created;
    }

    public function syncDepartment(Department $department, AcademicYear $academicYear): int
    {
        $count = 0;
        $sections = $department->sections()->get(['id', 'name', 'group_name']);
        $groupsBySectionId = $sections->pluck('group_name', 'id');
        $groupsBySectionName = $sections->pluck('group_name', 'name');

        Student::query()
            ->where('member_type', 'student')
            ->where('organization', $department->organization->slug)
            ->where('stream', $department->name)
            ->chunkById(200, function ($students) use ($academicYear, $department, $groupsBySectionId, $groupsBySectionName, &$count) {
                foreach ($students as $student) {
                    $groupName = $student->section_id
                        ? $groupsBySectionId->get($student->section_id)
                        : $groupsBySectionName->get($student->section);
                    $count += $this->replaceAssignments($student, $academicYear, $department, $groupName);
                }
            });

        return $count;
    }

    public function syncStudent(Student $student, ?AcademicYear $academicYear = null): int
    {
        if ($student->member_type !== 'student') return 0;

        $academicYear ??= $this->currentWritableAcademicYear();
        if (! $academicYear) return 0;

        $department = $student->department_record;
        $groupName = $student->section_id
            ? $student->academicSection?->group_name
            : null;
        if (! $groupName && $department && $student->section) {
            $groupName = $department->sections()->where('name', $student->section)->value('group_name');
        }

        return $this->replaceAssignments($student, $academicYear, $department, $groupName);
    }

    private function replaceAssignments(Student $student, AcademicYear $academicYear, ?Department $department, ?string $groupName): int
    {
        StudentSubjectEnrollment::where('student_id', $student->id)
            ->where('academic_year', $academicYear->name)
            ->where('assignment_source', 'automatic')
            ->delete();

        if (! $department) {
            StudentSubjectEnrollment::where('student_id', $student->id)
                ->where('academic_year', $academicYear->name)
                ->where('assignment_source', 'manual')
                ->delete();
            return 0;
        }

        $offerings = SubjectOffering::query()
            ->where('department_id', $department->id)
            ->where('is_elective', false)
            ->where(fn ($query) => $query->whereNull('semester')->orWhere('semester', $student->semester))
            ->where(fn ($query) => $query->whereNull('year_level')->orWhere('year_level', $student->year_level))
            ->where(fn ($query) => $query->whereNull('group_name')->when($groupName, fn ($query) => $query->orWhere('group_name', $groupName)))
            ->get();

        StudentSubjectEnrollment::with('offering.department.organization')
            ->where('student_id', $student->id)
            ->where('academic_year', $academicYear->name)
            ->where('assignment_source', 'manual')
            ->whereNotNull('subject_offering_id')
            ->get()
            ->each(function ($enrollment) use ($student) {
                if (! $enrollment->offering || ! $this->studentMatchesOffering($student, $enrollment->offering)) {
                    $enrollment->delete();
                }
            });

        foreach ($offerings as $offering) {
            StudentSubjectEnrollment::updateOrCreate(
                ['student_id' => $student->id, 'subject_id' => $offering->subject_id, 'academic_year' => $academicYear->name],
                ['subject_offering_id' => $offering->id, 'assignment_source' => 'automatic']
            );
        }

        return $offerings->count();
    }

    public function studentMatchesOffering(Student $student, SubjectOffering $offering): bool
    {
        return $this->eligibleStudentsQuery($offering)->whereKey($student->id)->exists();
    }

    public function eligibleStudentsQuery(SubjectOffering $offering): Builder
    {
        $offering->loadMissing('department.organization', 'department.sections');
        $query = Student::query()
            ->where('member_type', 'student')
            ->where('organization', $offering->department->organization->slug)
            ->where('stream', $offering->department->name)
            ->when($offering->semester, fn ($query) => $query->where('semester', $offering->semester))
            ->when($offering->year_level, fn ($query) => $query->where('year_level', $offering->year_level));

        if ($offering->group_name) {
            $sectionNames = $offering->department->sections
                ->where('group_name', $offering->group_name)
                ->pluck('name');
            $query->where(fn ($query) => $query
                ->whereHas('academicSection', fn ($query) => $query->where('group_name', $offering->group_name))
                ->orWhere(fn ($query) => $query->whereNull('section_id')->whereIn('section', $sectionNames)));
        }

        return $query;
    }
}
