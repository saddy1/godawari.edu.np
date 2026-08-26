<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPayslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_batch_id', 'user_id', 'staff_id', 'employee_name', 'bank_code',
        'account_number', 'pan_number', 'position', 'previous_dues_extra',
        'current_salary', 'meeting_extra_allowance', 'transportation', 'telephone',
        'absent_days', 'absence_amount', 'taxable_income', 'advance',
        'provident_fund', 'salary_before_tax', 'tax_amount', 'net_amount',
    ];

    protected function casts(): array
    {
        return [
            'previous_dues_extra' => 'decimal:2', 'current_salary' => 'decimal:2',
            'meeting_extra_allowance' => 'decimal:2', 'transportation' => 'decimal:2',
            'telephone' => 'decimal:2', 'absent_days' => 'decimal:2',
            'absence_amount' => 'decimal:2', 'taxable_income' => 'decimal:2',
            'advance' => 'decimal:2', 'provident_fund' => 'decimal:2',
            'salary_before_tax' => 'decimal:2', 'tax_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
        ];
    }

    public function batch()
    {
        return $this->belongsTo(PayrollBatch::class, 'payroll_batch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
