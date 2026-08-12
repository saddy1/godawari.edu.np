<?php

namespace Tests\Unit;

use App\Models\LibraryLoan;
use Carbon\Carbon;
use Tests\TestCase;

class LibraryLoanFineTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_active_loan_accrued_fine_subtracts_payments_already_collected(): void
    {
        Carbon::setTestNow('2026-08-04 12:00:00');

        $loan = new class([
            'status' => 'issued',
            'due_date' => '2026-08-01',
            'fine_paid' => 2,
        ]) extends LibraryLoan {
            protected function resolveFinePerDay(): float
            {
                return 2.0;
            }
        };

        // Three overdue days at the Rs. 2 fallback rate = Rs. 6 total,
        // with Rs. 2 already paid and Rs. 4 still outstanding.
        $this->assertSame(4.0, $loan->accrued_fine);
    }

    public function test_returned_loan_uses_stored_outstanding_fine(): void
    {
        $loan = new LibraryLoan([
            'status' => 'returned',
            'fine_amount' => 10,
            'fine_paid' => 3,
        ]);

        $this->assertSame(7.0, $loan->accrued_fine);
    }
}
