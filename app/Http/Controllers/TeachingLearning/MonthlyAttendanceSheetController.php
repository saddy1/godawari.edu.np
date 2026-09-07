<?php

namespace App\Http\Controllers\TeachingLearning;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Hajiri\NepaliCalendarController;
use App\Models\Card\Department;
use App\Models\Card\Organization;
use App\Models\Card\Section;
use App\Models\Card\Student;
use App\Models\TeachingLearning\AcademicYear;
use App\Models\TeachingLearning\RoutineStudentAttendance;
use Illuminate\Http\Request;

class MonthlyAttendanceSheetController extends Controller
{
    public function index(Request $request)
    {
        return view('teaching_learning.attendance-sheets.index', $this->sheetData($request));
    }

    public function print(Request $request)
    {
        $data = $this->sheetData($request, true);

        return view('teaching_learning.attendance-sheets.print', $data);
    }

    private function sheetData(Request $request, bool $forPrint = false): array
    {
        $calendar = new NepaliCalendarController();
        $today = now();
        $todayBs = $calendar->ad_2_bs((int) $today->format('Y'), (int) $today->format('m'), (int) $today->format('d'));
        $bsYear = $request->integer('bs_year') ?: (int) ($todayBs['year'] ?? 2083);
        $bsMonth = $request->integer('bs_month') ?: (int) ($todayBs['month'] ?? 1);
        $calendarYear = $calendar->getBSCal($bsYear);
        if (! $calendarYear || $bsMonth < 1 || $bsMonth > 12) {
            abort(422, 'Choose a valid Nepali year and month.');
        }

        $organizations = Organization::with(['logoAsset', 'departments' => fn ($query) => $query
            ->where('is_active', true)->with(['sections' => fn ($sections) => $sections->where('is_active', true)->orderBy('name')])])
            ->where('is_active', true)->orderBy('name')->get();
        $organization = $organizations->firstWhere('id', $request->integer('organization_id'));
        $department = $organization?->departments->firstWhere('id', $request->integer('department_id'));
        $section = $department?->sections->firstWhere('id', $request->integer('section_id'));

        if ($forPrint && ! $organization) {
            abort(422, 'Select an organization before printing.');
        }

        $academicYears = AcademicYear::latest('starts_on')->latest('id')->get();
        $academicYear = $academicYears->firstWhere('id', $request->integer('academic_year_id'))
            ?? $academicYears->firstWhere('is_active', true)
            ?? $academicYears->first();
        $gender = in_array($request->query('gender'), ['male', 'female', 'other'], true) ? $request->query('gender') : '';
        $sort = in_array($request->query('sort'), ['roll', 'name'], true) ? $request->query('sort') : 'roll';
        $content = $request->query('content') === 'recorded' ? 'recorded' : 'blank';
        $rowsPerPage = in_array($request->integer('rows_per_page'), [30, 35, 40, 45, 50], true)
            ? $request->integer('rows_per_page') : 50;

        $daysInMonth = (int) $calendarYear[$bsMonth];
        $days = collect(range(1, $daysInMonth))->map(function (int $day) use ($calendar, $bsYear, $bsMonth) {
            $ad = $calendar->bs_2_ad($bsYear, $bsMonth, $day);
            return [
                'number' => $day,
                'ad' => $ad ? sprintf('%04d-%02d-%02d', $ad['year'], $ad['month'], $ad['date']) : null,
                'weekday' => $ad['day'] ?? null,
            ];
        });

        $targetSections = collect();
        if ($organization && ! ($request->filled('department_id') && ! $department)) {
            if ($department && ! ($request->filled('section_id') && ! $section)) {
                $targetSections = $section ? collect([$section]) : $department->sections;
            } elseif (! $request->filled('department_id')) {
                $targetSections = $organization->departments->flatMap->sections;
            }
        }

        $sheets = $targetSections->map(function (Section $targetSection) use ($organization, $gender, $sort, $content, $academicYear, $days) {
            $targetDepartment = $targetSection->department
                ?? $organization->departments->firstWhere('id', $targetSection->department_id);
            $sectionStudents = $this->studentsForSection($organization, $targetDepartment, $targetSection, $gender, $sort);
            $sectionAttendance = $content === 'recorded' && $sectionStudents->isNotEmpty()
                ? $this->recordedAttendance($sectionStudents->pluck('id'), $targetSection, $academicYear, $days)
                : collect();

            return [
                'department' => $targetDepartment,
                'section' => $targetSection,
                'students' => $sectionStudents,
                'attendance' => $sectionAttendance,
            ];
        })->values();
        if ($forPrint && $sheets->isEmpty()) {
            abort(422, 'No active sections match the selected organization and faculty/class.');
        }

        $students = $sheets->flatMap(fn ($sheet) => $sheet['students'])->values();
        $attendance = $sheets->flatMap(fn ($sheet) => $sheet['attendance']);
        $monthLabel = $calendar->get_nepali_month($bsMonth) ?: 'Month '.$bsMonth;
        $yearOptions = collect(range(max($calendar->minBsYear(), $bsYear - 4), min($calendar->maxBsYear(), $bsYear + 4)));

        return compact(
            'organizations', 'organization', 'department', 'section', 'academicYears', 'academicYear',
            'students', 'days', 'attendance', 'sheets', 'bsYear', 'bsMonth', 'monthLabel', 'yearOptions',
            'gender', 'sort', 'content', 'rowsPerPage'
        );
    }

    private function studentsForSection(Organization $organization, Department $department, Section $section, string $gender, string $sort)
    {
        return Student::query()
            ->where('member_type', 'student')
            ->where('organization', $organization->slug)
            ->where(fn ($query) => $query->where('section_id', $section->id)
                ->orWhere(fn ($legacy) => $legacy->whereNull('section_id')->where('stream', $department->name)->where('section', $section->name)))
            ->when($gender, fn ($query) => $query->whereRaw('LOWER(gender) = ?', [$gender]))
            ->when($sort === 'name', fn ($query) => $query->orderBy('first_name')->orderBy('middle_name')->orderBy('last_name'),
                fn ($query) => $query->orderByRaw('roll_number IS NULL')->orderBy('roll_number')->orderBy('first_name'))
            ->get(['id', 'roll_number', 'registration_no', 'first_name', 'middle_name', 'last_name', 'gender']);
    }

    private function recordedAttendance($studentIds, Section $section, ?AcademicYear $academicYear, $days)
    {
        $dates = $days->pluck('ad')->filter();
        if ($dates->isEmpty()) return collect();

        return RoutineStudentAttendance::query()
            ->with('session:id,attendance_date')
            ->whereIn('student_id', $studentIds)
            ->whereHas('session', fn ($session) => $session
                ->whereBetween('attendance_date', [$dates->first(), $dates->last()])
                ->whereHas('lesson', fn ($lesson) => $lesson->where('section_id', $section->id)
                    ->when($academicYear, fn ($lesson) => $lesson->whereHas('plan', fn ($plan) => $plan->where('academic_year_id', $academicYear->id)))))
            ->get()
            ->groupBy(fn ($row) => $row->student_id.'|'.$row->session->attendance_date->format('Y-m-d'))
            ->map(function ($rows) {
                $statuses = $rows->pluck('status');
                return match (true) {
                    $statuses->contains('present') => 'P',
                    $statuses->contains('late') => 'L',
                    $statuses->contains('excused') => 'E',
                    default => 'A',
                };
            });
    }
}
