<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PayrollSpreadsheetService
{
    private const HEADERS = [
        'sn' => ['sn', 'serialno', 'serialnumber'],
        'staff_id' => ['staffid', 'employeeid', 'deviceid', 'hajirideviceno', 'hajiriid'],
        'employee_name' => ['name', 'employeename', 'staffname'],
        'bank_code' => ['bankcode', 'bankname'],
        'account_number' => ['accountno', 'accountnumber', 'bankaccountno'],
        'pan_number' => ['panno', 'pannumber', 'pan'],
        'position' => ['position', 'designation'],
        'previous_dues_extra' => ['previousduesextra', 'previousdueextra', 'previousdues', 'duesextra'],
        'current_salary' => ['currentsalary', 'basicsalary', 'salary'],
        'meeting_extra_allowance' => ['meetingallowanceextraallowances', 'meetingallowanceextraallowance', 'meetingextraallowance', 'extraallowance'],
        'transportation' => ['transportation', 'transportallowance', 'transport'],
        'telephone' => ['telephone', 'telephoneallowance', 'phoneallowance'],
        'absent_days' => ['noofabsday', 'noofabsdays', 'absentdays', 'absdays'],
        'absence_amount' => ['absamount', 'absenceamount', 'absentamount'],
        'taxable_income' => ['taxableincome'],
        'advance' => ['advance', 'salaryadvance'],
        'provident_fund' => ['providendfund', 'providentfund', 'pf'],
        'salary_before_tax' => ['salarybeforetax'],
        'tax_amount' => ['tax1', 'taxat1', 'tdsonsalary', 'taxamount', 'tax'],
        'net_amount' => ['netamountsalaryaftertax', 'netamount', 'netreceivable', 'salaryaftertax'],
    ];

    private const AMOUNT_FIELDS = [
        'previous_dues_extra', 'current_salary', 'meeting_extra_allowance',
        'transportation', 'telephone', 'absent_days', 'absence_amount',
        'taxable_income', 'advance', 'provident_fund', 'salary_before_tax',
        'tax_amount', 'net_amount',
    ];

    public function parse(string $path): array
    {
        try {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $rawRows = $sheet->toArray(null, true, true, false);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['payroll_file' => 'The spreadsheet could not be read: '.$exception->getMessage()]);
        }

        [$headerIndex, $columnMap] = $this->findHeaders($rawRows);
        $rows = [];
        $errors = [];
        $seenStaffIds = [];

        $staffIds = collect(array_slice($rawRows, $headerIndex + 1))
            ->map(fn (array $row) => $this->staffId($row[$columnMap['staff_id']] ?? null))
            ->filter()->unique()->values();
        $matchedUsers = User::query()->whereIn('device_id', $staffIds)->get();
        $usersByStaffId = $matchedUsers->groupBy(fn (User $user) => (string) $user->device_id);

        foreach (array_slice($rawRows, $headerIndex + 1, null, true) as $zeroIndex => $raw) {
            $staffId = $this->staffId($raw[$columnMap['staff_id']] ?? null);
            $hasAnyValue = collect($raw)->contains(fn ($value) => filled($value));
            if (! $hasAnyValue) {
                continue;
            }
            if ($staffId === '') {
                continue;
            }

            $excelRow = $zeroIndex + 1;
            $row = [];
            foreach ($columnMap as $field => $column) {
                $row[$field] = $raw[$column] ?? null;
            }
            $row['staff_id'] = $staffId;

            if (isset($seenStaffIds[$staffId])) {
                $errors[] = "Row {$excelRow}: Staff ID {$staffId} is repeated (first seen on row {$seenStaffIds[$staffId]}).";
            } else {
                $seenStaffIds[$staffId] = $excelRow;
            }

            $matches = $usersByStaffId->get($staffId, collect());
            $user = $matches->count() === 1 ? $matches->first() : null;
            if ($matches->count() > 1) {
                $errors[] = "Row {$excelRow}: Staff ID {$staffId} is assigned to more than one HR account. Fix the duplicate Staff ID in HR first.";
            }
            if (! $user) {
                if ($matches->isEmpty()) {
                    $errors[] = "Row {$excelRow}: Staff ID {$staffId} is not linked to any HR/Hajiri account.";
                }
            }

            foreach (self::AMOUNT_FIELDS as $field) {
                $value = $row[$field] ?? null;
                if (blank($value)) {
                    $row[$field] = 0;

                    continue;
                }
                $clean = str_replace([',', 'Rs.', 'Rs', 'रु', ' '], '', (string) $value);
                if (! is_numeric($clean)) {
                    $errors[] = "Row {$excelRow}: ".str_replace('_', ' ', $field).' must be a number.';
                    $row[$field] = 0;
                } else {
                    $row[$field] = round((float) $clean, 2);
                }
            }

            $row['employee_name'] = trim((string) ($row['employee_name'] ?? '')) ?: ($user?->name ?? '');
            $row['matched_name'] = $user?->name;
            $row['user_id'] = $user?->id;
            $row['excel_row'] = $excelRow;
            $row['warnings'] = [];
            if (abs(($row['salary_before_tax'] - $row['tax_amount']) - $row['net_amount']) > 1) {
                $row['warnings'][] = 'Net amount differs from salary before tax minus tax.';
            }
            if ($user && $this->normalize($row['employee_name']) !== $this->normalize($user->name)) {
                $row['warnings'][] = "Sheet name differs from HR account ({$user->name}).";
            }
            $rows[] = $row;
        }

        if ($rows === []) {
            $errors[] = 'No employee rows with a Staff ID were found.';
        }

        return ['rows' => $rows, 'errors' => $errors, 'header_row' => $headerIndex + 1];
    }

    private function findHeaders(array $rows): array
    {
        foreach (array_slice($rows, 0, 25, true) as $index => $row) {
            $map = [];
            foreach ($row as $column => $heading) {
                $normalized = $this->normalize($heading);
                foreach (self::HEADERS as $field => $aliases) {
                    if (! isset($map[$field]) && in_array($normalized, $aliases, true)) {
                        $map[$field] = $column;
                    }
                }
            }
            if (isset($map['staff_id'], $map['current_salary'], $map['net_amount'])) {
                return [$index, $map];
            }
        }

        throw ValidationException::withMessages([
            'payroll_file' => 'Could not find the salary header row. Required columns are Staff ID, Current Salary, and Net Amount.',
        ]);
    }

    private function normalize(mixed $value): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', trim((string) $value)));
    }

    private function staffId(mixed $value): string
    {
        $value = trim((string) $value);

        return preg_match('/^\d+\.0+$/', $value) ? (string) (int) $value : $value;
    }
}
