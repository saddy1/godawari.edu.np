<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('bs_year');
            $table->unsignedTinyInteger('bs_month');
            $table->unsignedInteger('revision')->default(1);
            $table->string('original_filename');
            $table->unsignedInteger('row_count')->default(0);
            $table->decimal('total_net_amount', 15, 2)->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->unique(['bs_year', 'bs_month']);
        });

        Schema::create('payroll_payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_batch_id')->constrained('payroll_batches')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('staff_id', 100);
            $table->string('employee_name');
            $table->string('bank_code', 100)->nullable();
            $table->string('account_number', 100)->nullable();
            $table->string('pan_number', 100)->nullable();
            $table->string('position')->nullable();
            $table->decimal('previous_dues_extra', 15, 2)->default(0);
            $table->decimal('current_salary', 15, 2)->default(0);
            $table->decimal('meeting_extra_allowance', 15, 2)->default(0);
            $table->decimal('transportation', 15, 2)->default(0);
            $table->decimal('telephone', 15, 2)->default(0);
            $table->decimal('absent_days', 8, 2)->default(0);
            $table->decimal('absence_amount', 15, 2)->default(0);
            $table->decimal('taxable_income', 15, 2)->default(0);
            $table->decimal('advance', 15, 2)->default(0);
            $table->decimal('provident_fund', 15, 2)->default(0);
            $table->decimal('salary_before_tax', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['payroll_batch_id', 'user_id']);
            $table->index(['user_id', 'payroll_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_payslips');
        Schema::dropIfExists('payroll_batches');
    }
};
