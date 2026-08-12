<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\LibraryPatronCategory;

class LibraryLoan extends Model
{
    protected $fillable = [
        'library_book_copy_id',
        'user_id',
        'student_id',
        'borrower_name',
        'borrower_identifier',
        'borrower_type',
        'issued_at',
        'due_date',
        'returned_at',
        'status',
        'fine_amount',
        'fine_paid',
        'payment_method',
        'payment_txn',
        'renewal_count',
        'renewed_at',
        'renewed_by',
        'remarks',
        'issued_by',
        'returned_by',
    ];

    protected $casts = [
        'issued_at'   => 'date',
        'due_date'    => 'date',
        'returned_at' => 'date',
        'renewed_at'  => 'date',
        'fine_amount' => 'decimal:2',
        'fine_paid'   => 'decimal:2',
    ];

    public function copy()
    {
        return $this->belongsTo(LibraryBookCopy::class, 'library_book_copy_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function student()
    {
        return $this->belongsTo(\App\Models\Card\Student::class, 'student_id');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function returner()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function getOutstandingFineAttribute(): float
    {
        return max(0, (float) $this->fine_amount - (float) $this->fine_paid);
    }

    /**
     * Live accrued fine for active overdue loans.
     * For returned loans it returns the stored outstanding fine.
     * For active overdue loans it computes days_late × fine_per_day
     * from the matching patron category (falls back to Rs. 2/day).
     */
    public function getAccruedFineAttribute(): float
    {
        if ($this->status !== 'issued') {
            return $this->outstanding_fine;
        }

        if (!$this->due_date || !$this->due_date->isPast()) {
            return 0.0;
        }

        $daysLate   = (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay());
        $finePerDay = $this->resolveFinePerDay();
        $totalFine  = round($daysLate * $finePerDay, 2);

        return max(0, round($totalFine - (float) $this->fine_paid, 2));
    }

    protected function resolveFinePerDay(): float
    {
        try {
            $user       = $this->user_id ? \App\Models\User::find($this->user_id) : null;
            $member     = $this->student_id ? \App\Models\Card\Student::find($this->student_id) : null;
            $categories = LibraryPatronCategory::where('is_active', true)->orderBy('sort_order')->get();

            if ($user || $member) {
                foreach ($categories as $cat) {
                    if (($user && $cat->matchesBorrower($user))
                        || (! $user && $member && $cat->matchesMember($member))) {
                        return (float) $cat->fine_per_day;
                    }
                }
            }

            // Fallback: default-all category or the lowest fine rate available
            $default = $categories->where('slug', 'default-all')->first() ?? $categories->first();
            return $default ? (float) $default->fine_per_day : 2.0;
        } catch (\Throwable) {
            return 2.0;
        }
    }
}
