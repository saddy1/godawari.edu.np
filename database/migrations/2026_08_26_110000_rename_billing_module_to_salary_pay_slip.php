<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('module_settings')->where('key', 'billing')->update([
            'label' => 'Salary / Pay Slip',
            'description' => 'Monthly salary uploads, employee payslips, receipts, and payment vouchers',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('module_settings')->where('key', 'billing')->update([
            'label' => 'Billing',
            'description' => 'Cash receipts, payment vouchers, and printable bills',
            'updated_at' => now(),
        ]);
    }
};
