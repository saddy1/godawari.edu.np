<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PayrollBatch;
use App\Models\PayrollPayslip;
use App\Services\PayrollSpreadsheetService;
use App\Support\MoneyWords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollController extends Controller
{
    public function index(Request $request): View
    {
        $batches = PayrollBatch::query()
            ->with('uploader')
            ->withCount('payslips')
            ->when($request->filled('year'), fn ($query) => $query->where('bs_year', $request->integer('year')))
            ->orderByDesc('bs_year')->orderByDesc('bs_month')->paginate(18)->withQueryString();

        return view('backend.payroll.index', [
            'batches' => $batches,
            'months' => PayrollBatch::BS_MONTHS,
        ]);
    }

    public function upload(): View
    {
        return view('backend.payroll.upload', [
            'months' => PayrollBatch::BS_MONTHS,
            'years' => range(2090, 2075),
        ]);
    }

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Monthly Salary');

        $headers = [
            'S.N.', 'Staff ID', 'NAME:', 'Bank Code', 'Account no:', 'PAN no:',
            'Position', 'previous Dues+ Extra', 'Current Salary',
            'meeting allowance + Extra allowances', 'Transportation', 'Telephone',
            'no. of abs. day', 'abs. amount', 'taxable income', 'advance',
            'Providend fund', 'salary before tax', 'tax@ 1%',
            'net amount (salary after tax)',
        ];

        $sheet->mergeCells('A1:T1')->setCellValue('A1', 'SUSHMA + GODAWARI');
        $sheet->mergeCells('A2:T2')->setCellValue('A2', 'MONTHLY SALARY UPLOAD TEMPLATE');
        $sheet->fromArray($headers, null, 'A4');
        $sheet->freezePane('A5');
        $sheet->setAutoFilter('A4:T104');
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getRowDimension(4)->setRowHeight(48);

        $sheet->getStyle('A1:T1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A5632']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A2:T2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '8A5A00']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF4D6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A4:T4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '237042']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ]);
        $sheet->getStyle('A5:T104')->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('B5:F104')->getNumberFormat()->setFormatCode('@');
        foreach (range(5, 104) as $row) {
            $sheet->setCellValue("A{$row}", $row - 4);
            $sheet->getCell("B{$row}")->setValueExplicit('', DataType::TYPE_STRING);
        }
        foreach (['A' => 7, 'B' => 13, 'C' => 24, 'D' => 14, 'E' => 18, 'F' => 15, 'G' => 18] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        foreach (range('H', 'T') as $column) {
            $sheet->getColumnDimension($column)->setWidth(17);
        }

        $instructions = $spreadsheet->createSheet();
        $instructions->setTitle('Instructions');
        $instructions->setCellValue('A1', 'How to prepare the monthly payroll file');
        $instructions->fromArray([
            ['1.', 'Choose the BS month and year on the Salary / Pay Slip upload page.'],
            ['2.', 'Staff ID is required and must exactly match the employee Hajiri Device ID in HR.'],
            ['3.', 'Do not rename, remove, merge, or reorder the heading row on the Monthly Salary sheet.'],
            ['4.', 'Enter zero or leave an optional amount blank when it does not apply.'],
            ['5.', 'Current Salary and Net Amount are required columns. Review all values in the website preview before confirming.'],
            ['6.', 'For corrections, select the same BS month/year and upload the corrected file again.'],
        ], null, 'A3');
        $instructions->getStyle('A1:B1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('1A5632');
        $instructions->getStyle('A3:B8')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $instructions->getColumnDimension('A')->setWidth(7);
        $instructions->getColumnDimension('B')->setWidth(95);
        foreach (range(3, 8) as $row) {
            $instructions->getRowDimension($row)->setRowHeight(34);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $filename = 'monthly-salary-upload-template.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function preview(Request $request, PayrollSpreadsheetService $parser): View|RedirectResponse
    {
        $validated = $request->validate([
            'bs_month' => ['required', 'integer', 'between:1,12'],
            'bs_year' => ['required', 'integer', 'between:2070,2100'],
            'payroll_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $oldPath = session()->pull('payroll_preview.path');
        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }

        $token = (string) Str::uuid();
        $file = $request->file('payroll_file');
        $path = $file->storeAs('payroll-previews', $token.'.'.$file->getClientOriginalExtension(), 'local');
        try {
            $result = $parser->parse(Storage::disk('local')->path($path));
        } catch (ValidationException $exception) {
            Storage::disk('local')->delete($path);
            session()->forget('payroll_preview');

            throw $exception;
        }
        $batch = PayrollBatch::where('bs_year', $validated['bs_year'])->where('bs_month', $validated['bs_month'])->first();

        session(['payroll_preview' => [
            'token' => $token,
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'bs_month' => (int) $validated['bs_month'],
            'bs_year' => (int) $validated['bs_year'],
        ]]);

        return view('backend.payroll.preview', [
            'token' => $token,
            'rows' => $result['rows'],
            'errors' => $result['errors'],
            'batch' => $batch,
            'monthName' => PayrollBatch::BS_MONTHS[(int) $validated['bs_month']],
            'year' => (int) $validated['bs_year'],
            'filename' => $file->getClientOriginalName(),
        ]);
    }

    public function commit(Request $request, PayrollSpreadsheetService $parser): RedirectResponse
    {
        $request->validate(['token' => ['required', 'uuid']]);
        $preview = session('payroll_preview');
        abort_unless($preview && hash_equals($preview['token'], $request->string('token')->toString()), 419, 'This payroll preview has expired. Upload the file again.');

        try {
            $result = $parser->parse(Storage::disk('local')->path($preview['path']));
        } catch (ValidationException $exception) {
            Storage::disk('local')->delete($preview['path']);
            session()->forget('payroll_preview');

            return redirect()->route('admin.billing.payroll.upload')->withErrors($exception->errors());
        }
        if ($result['errors']) {
            Storage::disk('local')->delete($preview['path']);
            session()->forget('payroll_preview');

            return redirect()->route('admin.billing.payroll.upload')
                ->withErrors(['payroll_file' => 'The file is no longer valid: '.implode(' ', $result['errors'])]);
        }

        $batch = DB::transaction(function () use ($preview, $result) {
            $batch = PayrollBatch::query()
                ->where('bs_year', $preview['bs_year'])->where('bs_month', $preview['bs_month'])
                ->lockForUpdate()->first();

            if ($batch) {
                $batch->payslips()->delete();
                $batch->update([
                    'revision' => $batch->revision + 1,
                    'original_filename' => $preview['filename'],
                    'row_count' => count($result['rows']),
                    'total_net_amount' => collect($result['rows'])->sum('net_amount'),
                    'uploaded_by' => auth()->id(),
                    'uploaded_at' => now(),
                ]);
            } else {
                $batch = PayrollBatch::create([
                    'bs_year' => $preview['bs_year'], 'bs_month' => $preview['bs_month'],
                    'revision' => 1, 'original_filename' => $preview['filename'],
                    'row_count' => count($result['rows']),
                    'total_net_amount' => collect($result['rows'])->sum('net_amount'),
                    'uploaded_by' => auth()->id(), 'uploaded_at' => now(),
                ]);
            }

            $fields = (new PayrollPayslip)->getFillable();
            foreach ($result['rows'] as $row) {
                $batch->payslips()->create(Arr::only($row, array_diff($fields, ['payroll_batch_id'])));
            }

            return $batch;
        });

        Storage::disk('local')->delete($preview['path']);
        session()->forget('payroll_preview');

        return redirect()->route('admin.billing.payroll.batch', $batch)
            ->with('success', 'Payroll for '.$batch->period_label.' was '.($batch->revision > 1 ? 're-uploaded' : 'uploaded').' successfully.');
    }

    public function batch(PayrollBatch $batch): View
    {
        $batch->load(['payslips' => fn ($query) => $query->orderBy('employee_name'), 'uploader']);

        return view('backend.payroll.batch', compact('batch'));
    }

    public function show(PayrollPayslip $payslip): View
    {
        $payslip->load(['batch', 'user.organization']);

        return $this->payslipView($payslip, route('admin.billing.payroll.batch', $payslip->batch));
    }

    public function printBatch(PayrollBatch $batch): View
    {
        $batch->load(['payslips.user.organization']);

        return view('backend.payroll.print-batch', compact('batch'));
    }

    public function myIndex(): View
    {
        $payslips = PayrollPayslip::query()->where('user_id', auth()->id())
            ->with('batch')->join('payroll_batches', 'payroll_batches.id', '=', 'payroll_payslips.payroll_batch_id')
            ->select('payroll_payslips.*')->orderByDesc('payroll_batches.bs_year')->orderByDesc('payroll_batches.bs_month')
            ->paginate(18);

        return view('backend.payroll.my-index', compact('payslips'));
    }

    public function myShow(PayrollPayslip $payslip): View
    {
        abort_unless($payslip->user_id === auth()->id(), 403);
        $payslip->load(['batch', 'user.organization']);

        return $this->payslipView($payslip, route('payroll.mine'));
    }

    private function payslipView(PayrollPayslip $payslip, string $backUrl): View
    {
        return view('backend.payroll.show', [
            'payslip' => $payslip,
            'backUrl' => $backUrl,
            'amountWords' => MoneyWords::rupees((float) $payslip->net_amount),
        ]);
    }
}
