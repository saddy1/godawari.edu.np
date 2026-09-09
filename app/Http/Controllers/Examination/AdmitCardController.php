<?php

namespace App\Http\Controllers\Examination;

use App\Http\Controllers\Controller;
use App\Models\Card\Student;
use App\Models\Card\Department;
use App\Services\ExamSymbolNumberService;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Examination\Examination;
use App\Models\Examination\ExaminationSymbolNumber;
use App\Services\ExamRosterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AdmitCardController extends Controller
{
    public function index(Request $request, Examination $examination, ExamRosterService $roster)
    {
        $request->validate(['q' => 'nullable|string|max:150', 'school_class' => 'nullable|in:11,12', 'page' => 'nullable|integer|min:1']);
        $examination->load(['organization', 'academicYear', 'departments', 'sections']);
        $students = $this->studentsWithClass($examination, $roster);
        $totalStudents = $students->count();
        $symbolNumbers = ExaminationSymbolNumber::where('examination_id', $examination->id)->pluck('symbol_no', 'student_id');
        $filterOptions = $students->map(fn ($student) => [
            'school_class' => $student->school_class,
            'faculty' => $student->stream,
            'section' => $student->academicSection?->name ?? $student->section,
        ])->unique(fn ($row) => json_encode($row))->values();
        $students = $this->filterStudents($request, $students, $symbolNumbers);
        $students = new LengthAwarePaginator($students->forPage($request->integer('page', 1), 30), $students->count(), 30, $request->integer('page', 1), ['path' => $request->url(), 'query' => $request->query()]);
        $data = compact('examination', 'students', 'symbolNumbers', 'totalStudents', 'filterOptions');
        if ($request->routeIs('admin.examinations.admit-cards.search')) {
            return response()->json(['html' => view('examinations.admit-cards._roster', $data)->render()])
                ->header('Cache-Control', 'private, no-store, max-age=0');
        }
        return response()->view('examinations.admit-cards.index', $data)
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function exportExcel(Request $request, Examination $examination, ExamRosterService $roster)
    {
        $students = $this->studentsWithClass($examination, $roster);
        $symbolNumbers = ExaminationSymbolNumber::where('examination_id', $examination->id)->pluck('symbol_no', 'student_id');
        $students = $this->filterStudents($request, $students, $symbolNumbers);

        $spreadsheet = new Spreadsheet;
        $this->writeRosterSheet($spreadsheet->getActiveSheet(), 'All', $students, $symbolNumbers);

        if ($examination->organization->type === 'school') {
            foreach ($students->pluck('school_class')->filter()->unique()->sort() as $class) {
                $classStudents = $students->filter(fn ($student) => $student->school_class === $class)->values();
                $this->writeRosterSheet($spreadsheet->createSheet(), "Class {$class}", $classStudents, $symbolNumbers);
            }
        }
        $spreadsheet->setActiveSheetIndex(0);

        $filename = str($examination->name)->slug('_').'_admit_card_roster.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function writeRosterSheet($sheet, string $title, Collection $students, Collection $symbolNumbers): void
    {
        $sheet->setTitle($title);

        $sheet->fromArray(['Symbol No.', 'Name', 'Class', 'Section', 'Remarks'], null, 'A1');
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A5632']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:E1');

        $row = 2;
        foreach ($students as $student) {
            $sheet->setCellValue("A{$row}", $symbolNumbers->get($student->id) ?? '');
            $sheet->setCellValue("B{$row}", $student->full_name);
            $sheet->setCellValue("C{$row}", $student->stream ?? '');
            $sheet->setCellValue("D{$row}", $student->academicSection?->name ?? $student->section ?? '');
            $sheet->setCellValue("E{$row}", '');
            $row++;
        }
        if ($row > 2) {
            $sheet->getStyle('A2:E'.($row - 1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);
        }
        foreach (['A' => 14, 'B' => 30, 'C' => 24, 'D' => 14, 'E' => 32] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function filterStudents(Request $request, Collection $students, Collection $symbolNumbers): Collection
    {
        $request->validate([
            'q' => 'nullable|string|max:150', 'school_class' => 'nullable|in:11,12',
            'faculty' => 'nullable|string|max:150', 'section' => 'nullable|string|max:150',
        ]);
        $query = mb_strtolower(trim((string) $request->input('q', '')));
        return $students->filter(function ($student) use ($query, $request, $symbolNumbers) {
            return (! $request->filled('school_class') || $student->school_class === $request->integer('school_class'))
                && (! $request->filled('faculty') || $student->stream === $request->input('faculty'))
                && (! $request->filled('section') || ($student->academicSection?->name ?? $student->section) === $request->input('section'))
                && ($query === '' || str_contains(mb_strtolower(implode(' ', [
                    $student->full_name, $student->roll_number, $student->stream,
                    $student->academicSection?->name ?? $student->section, $symbolNumbers->get($student->id),
                ])), $query));
        })->values();
    }

    public function assignSymbolNumbers(Request $request, Examination $examination, ExamRosterService $roster, ExamSymbolNumberService $symbols)
    {
        $data = $examination->organization->type === 'school'
            ? ['start_number' => 1]
            : $request->validate(['start_number' => ['required', 'integer', 'min:1', 'max:1000000000']]);
        $count = DB::transaction(function () use ($roster, $examination, $data, $symbols) {
            Examination::whereKey($examination->id)->lockForUpdate()->firstOrFail();
            if (ExaminationSymbolNumber::where('examination_id', $examination->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['symbol_numbers' => 'Symbol numbers have already been assigned for this exam.']);
            }
            $students = $this->studentsWithClass($examination, $roster);
            abort_if($students->isEmpty(), 422, 'No students are enrolled for this exam yet.');
            $numbers = $symbols->numbers($students, $examination->organization->type === 'school', (int) $data['start_number']);
            foreach ($numbers as $studentId => $number) {
                ExaminationSymbolNumber::create(['examination_id' => $examination->id, 'student_id' => $studentId, 'symbol_no' => $number]);
            }
            return $students->count();
        });
        return back()->with('success', 'Symbol numbers assigned to '.$count.' student(s).');
    }

    private function studentsWithClass(Examination $examination, ExamRosterService $roster): Collection
    {
        $departments = Department::where('organization_id', $examination->organization_id)->get()->keyBy('name');
        return $roster->studentsForExam($examination)->each(function ($student) use ($departments) {
            $student->school_class = $student->academicSection?->department?->school_class ?? $departments->get($student->stream)?->school_class;
        });
    }

    public function printOne(Examination $examination, Student $student, ExamRosterService $roster)
    {
        return view('examinations.admit-cards.print', [
            'examination' => $examination,
            'roster' => $this->singleRoster($examination, $student, $roster),
            'count' => 1,
            'singleStudent' => $student,
        ]);
    }

    private function singleRoster(Examination $examination, Student $student, ExamRosterService $roster): Collection
    {
        abort_unless($roster->studentsForExam($examination)->contains('id', $student->id), 404);
        $this->eagerLoadBranding($examination);
        $symbolNo = ExaminationSymbolNumber::where('examination_id', $examination->id)->where('student_id', $student->id)->value('symbol_no');
        abort_if(! $symbolNo, 404, 'Assign a symbol number to this student first.');
        $subjects = $roster->subjectsForStudent($examination, $student);
        return collect([['student' => $student, 'symbol_no' => $symbolNo, 'subjects' => $subjects, 'department' => $subjects->first()?->offering?->department]]);
    }

    public function print(Request $request, Examination $examination, ExamRosterService $roster)
    {
        $count = $this->resolveCount($request);

        return view('examinations.admit-cards.print', [
            'examination' => $examination,
            'roster' => $this->rosterWithSymbols($request, $examination, $roster),
            'count' => $count,
        ]);
    }

    public function downloadAll(Request $request, Examination $examination, ExamRosterService $roster)
    {
        $count = $this->resolveCount($request);

        $pdf = Pdf::loadView('examinations.admit-cards.pdf', [
            'examination' => $examination,
            'roster' => $this->rosterWithSymbols($request, $examination, $roster),
            'count' => $count,
        ])->setPaper('a4', 'portrait');

        return $pdf->download(str($examination->name)->slug('_').'_admit_cards.pdf');
    }

    public function downloadOne(Examination $examination, Student $student, ExamRosterService $roster)
    {
        $pdf = Pdf::loadView('examinations.admit-cards.pdf', [
            'examination' => $examination,
            'count' => 1,
            'roster' => $this->singleRoster($examination, $student, $roster),
        ])->setPaper('a4', 'portrait');

        return $pdf->download(str($student->full_name)->slug('_').'_admit_card.pdf');
    }

    private function resolveCount(Request $request): int
    {
        $count = (int) $request->input('count', 1);

        return in_array($count, [1, 2, 4], true) ? $count : 1;
    }

    private function eagerLoadBranding(Examination $examination): void
    {
        $examination->loadMissing(['organization.logoAsset', 'organization.signatureAsset', 'organization.stampAsset', 'academicYear']);
    }

    private function rosterWithSymbols(Request $request, Examination $examination, ExamRosterService $roster): Collection
    {
        $this->eagerLoadBranding($examination);
        $students = $this->studentsWithClass($examination, $roster);
        $symbolNumbers = ExaminationSymbolNumber::where('examination_id', $examination->id)->pluck('symbol_no', 'student_id');

        return $this->filterStudents($request, $students, $symbolNumbers)->filter(fn ($student) => $symbolNumbers->has($student->id))
            ->map(function ($student) use ($examination, $roster, $symbolNumbers) {
                $subjects = $roster->subjectsForStudent($examination, $student);

                return [
                    'student' => $student,
                    'symbol_no' => $symbolNumbers->get($student->id),
                    'subjects' => $subjects,
                    'department' => $subjects->first()?->offering?->department,
                ];
            })->values();
    }
}
