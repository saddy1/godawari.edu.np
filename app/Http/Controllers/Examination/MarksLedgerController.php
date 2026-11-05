<?php

namespace App\Http\Controllers\Examination;

use App\Http\Controllers\Controller;
use App\Models\Examination\Examination;
use App\Models\Examination\ExaminationSymbolNumber;
use App\Services\ExamResultService;
use App\Services\ExamRosterService;
use App\Services\GradeService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MarksLedgerController extends Controller
{
    private const BRAND_GREEN = '1A5632';

    public function index(Request $request, Examination $examination, ExamRosterService $roster, GradeService $grades)
    {
        $ledger = $this->buildLedger($examination, $roster, $grades);

        return view('examinations.ledger.index', ['examination' => $examination, 'ledger' => $ledger]);
    }

    public function download(Request $request, Examination $examination, ExamRosterService $roster, GradeService $grades)
    {
        $ledger = $this->buildLedger($examination, $roster, $grades);
        $spreadsheet = $this->buildSpreadsheet($examination, $ledger);
        $filename = str($examination->name)->slug('_').'_marks_ledger.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function buildLedger(Examination $examination, ExamRosterService $roster, GradeService $grades): array
    {
        $examination->loadMissing(['organization', 'academicYear']);
        $students = $roster->studentsForExam($examination);
        $symbolNumbers = ExaminationSymbolNumber::where('examination_id', $examination->id)->pluck('symbol_no', 'student_id');
        $service = new ExamResultService($roster, $grades);

        $rows = $students->map(function ($student) use ($examination, $service, $symbolNumbers) {
            $result = $service->resultFor($examination, $student, (int) ($symbolNumbers->get($student->id) ?: 0));
            $department = $result['department'];

            return [
                'student'         => $student,
                'symbol_no'       => $result['symbol_no'] ?: null,
                'department_name' => $department?->name ?: ($student->stream ?: 'Unassigned'),
                'section_name'    => $student->academicSection?->name ?: ($student->section ?: 'Unassigned'),
                'gender'          => $student->gender,
                'subjectRows'     => $result['subjectRows'],
                'total_obtained'  => $result['total_obtained'],
                'total_full'      => $result['total_full'],
                'percentage'      => $result['percentage'],
                'gpa'             => $result['gpa'],
                'overall_grade'   => $result['overall_grade'],
                'complete'        => $result['complete'],
            ];
        });

        $departments = $rows->groupBy('department_name')->map(function (Collection $deptRows, string $deptName) {
            // Union of subjects actually configured for this department's students — a
            // student who skipped an elective just shows blank under that column.
            $subjectColumns = $deptRows->flatMap(fn ($row) => $row['subjectRows'])
                ->unique(fn ($row) => $row['subject_id'].':'.$row['component'])
                ->sortBy(fn ($row) => $row['name'].$row['component'])
                ->values();

            return [
                'name'            => $deptName,
                'subject_columns' => $subjectColumns,
                'rows'            => $deptRows->sort(fn ($a, $b) => [$a['section_name'], $a['student']->full_name] <=> [$b['section_name'], $b['student']->full_name])->values(),
            ];
        })->sortKeys()->values();

        return [
            'departments'   => $departments,
            'stats'         => $this->buildStats($rows),
            'total_students' => $rows->count(),
        ];
    }

    private function buildStats(Collection $rows): array
    {
        $ranked = $rows->filter(fn ($row) => $row['complete'] && $row['percentage'] !== null);

        $sectionToppers = $ranked->groupBy('section_name')->map(fn ($group) => $group->sortByDesc('percentage')->first())->sortKeys();
        $facultyToppers = $ranked->groupBy('department_name')->map(fn ($group) => $group->sortByDesc('percentage')->first())->sortKeys();
        $topMale = $ranked->filter(fn ($row) => strtolower((string) $row['gender']) === 'male')->sortByDesc('percentage')->take(5)->values();
        $topFemale = $ranked->filter(fn ($row) => strtolower((string) $row['gender']) === 'female')->sortByDesc('percentage')->take(5)->values();

        return [
            'section_toppers' => $sectionToppers,
            'faculty_toppers' => $facultyToppers,
            'top_male'        => $topMale,
            'top_female'      => $topFemale,
        ];
    }

    private function buildSpreadsheet(Examination $examination, array $ledger): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        foreach ($ledger['departments'] as $dept) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(substr(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $dept['name']), 0, 31) ?: 'Sheet');
            $this->fillDepartmentSheet($sheet, $examination, $dept);
        }

        $statsSheet = $spreadsheet->createSheet();
        $statsSheet->setTitle('Statistics');
        $this->fillStatsSheet($statsSheet, $ledger['stats']);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function fillDepartmentSheet(Worksheet $sheet, Examination $examination, array $dept): void
    {
        $columns = $dept['subject_columns'];
        $headerRow1 = ['S.N.', 'Symbol No.', 'Roll No.', 'Student Name', 'Gender', 'Section'];
        $headerRow2 = ['', '', '', '', '', ''];
        foreach ($columns as $col) {
            $headerRow1[] = $col['name'].' ('.$col['label'].')';
            $headerRow2[] = 'FM '.$col['full'];
        }
        foreach (['Total Obtained', 'Total Full', 'Percentage', 'GPA', 'Grade'] as $extra) {
            $headerRow1[] = $extra;
            $headerRow2[] = '';
        }
        $lastColLetter = Coordinate::stringFromColumnIndex(count($headerRow1));

        $sheet->setCellValue('A1', $examination->name.' — '.$dept['name'].' — Marks Ledger');
        $sheet->mergeCells('A1:'.$lastColLetter.'1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->fromArray($headerRow1, null, 'A3');
        $sheet->fromArray($headerRow2, null, 'A4');
        $sheet->getStyle('A3:'.$lastColLetter.'4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::BRAND_GREEN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->freezePane('A5');

        $r = 5;
        foreach ($dept['rows'] as $i => $row) {
            $marksBySubject = collect($row['subjectRows'])->keyBy(fn ($sr) => $sr['subject_id'].':'.$sr['component']);
            $line = [$i + 1, $row['symbol_no'] ?: '-', $row['student']->roll_number ?: '-', $row['student']->full_name, ucfirst((string) $row['gender']) ?: '-', $row['section_name']];
            foreach ($columns as $col) {
                $sr = $marksBySubject->get($col['subject_id'].':'.$col['component']);
                $line[] = $sr === null ? '—' : ($sr['absent'] ? 'AB' : ($sr['obtained'] ?? '—'));
            }
            $line[] = $row['total_obtained'];
            $line[] = $row['total_full'];
            $line[] = $row['percentage'] !== null ? $row['percentage'].'%' : 'Incomplete';
            $line[] = $row['gpa'] ?? '—';
            $line[] = $row['overall_grade'] ?? '—';
            $sheet->fromArray($line, null, 'A'.$r);
            $r++;
        }

        for ($i = 1; $i <= count($headerRow1); $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }

    private function fillStatsSheet(Worksheet $sheet, array $stats): void
    {
        $r = 1;
        $section = function (string $title, array $headers) use ($sheet, &$r) {
            $sheet->setCellValue('A'.$r, $title);
            $sheet->getStyle('A'.$r)->getFont()->setBold(true)->setSize(13)->getColor()->setRGB(self::BRAND_GREEN);
            $r += 2;
            $sheet->fromArray($headers, null, 'A'.$r);
            $sheet->getStyle('A'.$r.':'.Coordinate::stringFromColumnIndex(count($headers)).$r)->getFont()->setBold(true);
            $r++;
        };

        $section('Section Toppers', ['Section', 'Student', 'Percentage', 'GPA', 'Grade']);
        foreach ($stats['section_toppers'] as $sectionName => $row) {
            $sheet->fromArray([$sectionName, $row['student']->full_name, $row['percentage'].'%', $row['gpa'] ?? '—', $row['overall_grade'] ?? '—'], null, 'A'.$r);
            $r++;
        }
        $r += 2;

        $section('Faculty Toppers', ['Faculty', 'Student', 'Percentage', 'GPA', 'Grade']);
        foreach ($stats['faculty_toppers'] as $facultyName => $row) {
            $sheet->fromArray([$facultyName, $row['student']->full_name, $row['percentage'].'%', $row['gpa'] ?? '—', $row['overall_grade'] ?? '—'], null, 'A'.$r);
            $r++;
        }
        $r += 2;

        $section('Top 5 Male Students', ['Rank', 'Student', 'Faculty', 'Section', 'Percentage', 'GPA']);
        foreach ($stats['top_male'] as $i => $row) {
            $sheet->fromArray([$i + 1, $row['student']->full_name, $row['department_name'], $row['section_name'], $row['percentage'].'%', $row['gpa'] ?? '—'], null, 'A'.$r);
            $r++;
        }
        $r += 2;

        $section('Top 5 Female Students', ['Rank', 'Student', 'Faculty', 'Section', 'Percentage', 'GPA']);
        foreach ($stats['top_female'] as $i => $row) {
            $sheet->fromArray([$i + 1, $row['student']->full_name, $row['department_name'], $row['section_name'], $row['percentage'].'%', $row['gpa'] ?? '—'], null, 'A'.$r);
            $r++;
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
