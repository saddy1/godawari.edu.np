@php
    $earningTotal = (float)$payslip->previous_dues_extra + (float)$payslip->current_salary + (float)$payslip->meeting_extra_allowance + (float)$payslip->transportation + (float)$payslip->telephone;
    $deductionTotal = (float)$payslip->tax_amount + (float)$payslip->provident_fund + (float)$payslip->absence_amount + (float)$payslip->advance;
    $schoolName = $payslip->user?->organization?->name ?: $siteSettings->localized('site_name', 'Sushma Godawari College');
@endphp
<article class="payslip-sheet">
    <header class="slip-heading"><h1>{{ $schoolName }}</h1><h2>{{ $siteSettings->get('contact_address', 'Itahari, Sunsari (Nepal)') }}</h2></header>
    <table class="slip-table identity"><tr><th>Employee Name:</th><td colspan="3">{{ $payslip->employee_name }}</td></tr><tr><th>Designation:</th><td colspan="3">{{ $payslip->position ?: '—' }}</td></tr><tr><th>Month:</th><td>{{ \App\Models\PayrollBatch::BS_MONTHS[$payslip->batch->bs_month] }}</td><th>Year:</th><td>{{ $payslip->batch->bs_year }} BS</td></tr><tr><th>Staff ID:</th><td>{{ $payslip->staff_id }}</td><th>Date:</th><td>{{ $payslip->batch->uploaded_at?->format('Y-m-d') }}</td></tr></table>
    <table class="slip-table salary"><thead><tr><th colspan="2">Earnings</th><th colspan="2">Deductions</th></tr><tr><th>Particulars</th><th>Amount in Rs</th><th>Particulars</th><th>Amount in Rs</th></tr></thead><tbody>
        <tr><td>Previous Dues + Extra</td><td>{{ number_format($payslip->previous_dues_extra,2) }}</td><td>TDS on Salary</td><td>{{ number_format($payslip->tax_amount,2) }}</td></tr>
        <tr><td>Basic Salary</td><td>{{ number_format($payslip->current_salary,2) }}</td><td>Provident Fund</td><td>{{ number_format($payslip->provident_fund,2) }}</td></tr>
        <tr><td>Meeting + Extra Allowance</td><td>{{ number_format($payslip->meeting_extra_allowance,2) }}</td><td>Abs. Amount ({{ number_format($payslip->absent_days,2) }} days)</td><td>{{ number_format($payslip->absence_amount,2) }}</td></tr>
        <tr><td>Transportation</td><td>{{ number_format($payslip->transportation,2) }}</td><td>Advance</td><td>{{ number_format($payslip->advance,2) }}</td></tr>
        <tr><td>Telephone</td><td>{{ number_format($payslip->telephone,2) }}</td><th>Net Receivable</th><th>{{ number_format($payslip->net_amount,2) }}</th></tr>
        <tr class="totals"><th>Total Earnings</th><th>{{ number_format($earningTotal,2) }}</th><th>Total Deductions</th><th>{{ number_format($deductionTotal,2) }}</th></tr>
    </tbody></table>
    <div class="words"><b>In Words:</b> {{ $amountWords ?? \App\Support\MoneyWords::rupees((float) $payslip->net_amount) }}</div>
    <table class="slip-table bank"><tr><th>Bank Code:</th><td>{{ $payslip->bank_code ?: '—' }}</td><th>Account No:</th><td>{{ $payslip->account_number ?: '—' }}</td></tr><tr><th>PAN No:</th><td>{{ $payslip->pan_number ?: '—' }}</td><th>Salary Before Tax:</th><td>Rs. {{ number_format($payslip->salary_before_tax,2) }}</td></tr></table>
    <div class="signatures"><div><span>................................</span><b>Signature of Employee</b></div><div><span>................................</span><b>Finance Dept.</b></div></div>
</article>
