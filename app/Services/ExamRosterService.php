<?php

namespace App\Services;

use App\Models\Card\Student;
use App\Models\Card\StudentSubjectEnrollment;
use App\Models\Examination\Examination;
use Illuminate\Support\Collection;

class ExamRosterService
{
    // Students expected to sit this exam: enrolled (current academic year) in one of
    // the exam's configured subject offerings, and inside one of the exam's sections.
    // Mirrors the eligibility shape already used in ExamAnalyticsService::forExam()
    // and MarkEntryController::eligibleStudents().
    public function studentsForExam(Examination $examination): Collection
    {
        $examination->loadMissing(['academicYear', 'sections', 'subjects']);
        $offeringIds = $examination->subjects->pluck('subject_offering_id')->unique();
        if ($offeringIds->isEmpty()) return collect();

        $sectionIds = $examination->sections->pluck('id');
        $sectionNames = $examination->sections->pluck('name');

        $studentIds = StudentSubjectEnrollment::query()
            ->where('academic_year', $examination->academicYear->name)
            ->whereIn('subject_offering_id', $offeringIds)
            ->whereHas('student', fn ($q) => $q->where('member_type', 'student')
                ->where(fn ($q) => $q->whereIn('section_id', $sectionIds)
                    ->orWhere(fn ($q) => $q->whereNull('section_id')->whereIn('section', $sectionNames))))
            ->distinct()->pluck('student_id');

        return Student::query()->with('academicSection.department')->whereIn('id', $studentIds)
            ->orderBy('stream')->orderBy('section')
            ->orderByRaw('roll_number IS NULL')->orderBy('roll_number')
            ->orderBy('first_name')->orderBy('id')->get();
    }

    // The exam's configured subjects that apply to one student, via that
    // student's current-year subject-offering enrollments.
    public function subjectsForStudent(Examination $examination, Student $student): Collection
    {
        $examination->loadMissing(['academicYear', 'subjects.offering.subject']);
        $offeringIds = StudentSubjectEnrollment::query()
            ->where('student_id', $student->id)
            ->where('academic_year', $examination->academicYear->name)
            ->pluck('subject_offering_id');

        return $examination->subjects
            ->whereIn('subject_offering_id', $offeringIds)
            ->sortBy(fn ($subject) => $subject->offering->subject->name)
            ->values();
    }
}
