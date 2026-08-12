<?php

namespace App\Models;

use App\Models\Card\Student;
use Illuminate\Database\Eloquent\Model;

class LibraryPatronCategory extends Model
{
    protected $fillable = [
        'name', 'slug', 'patron_type', 'organization_slug', 'department_name',
        'class_range', 'class_from', 'class_to',
        'library_category_id', 'max_active_books', 'loan_days', 'fine_per_day',
        'block_same_title', 'description', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'max_active_books'   => 'integer',
        'loan_days'          => 'integer',
        'fine_per_day'       => 'decimal:2',
        'block_same_title'   => 'boolean',
        'is_active'          => 'boolean',
        'sort_order'         => 'integer',
        'class_from'         => 'integer',
        'class_to'           => 'integer',
        'library_category_id' => 'integer',
    ];

    public function catalogCategory()
    {
        return $this->belongsTo(LibraryCategory::class, 'library_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getClassRangeLabelAttribute(): string
    {
        if ($this->department_name) {
            return $this->department_name;
        }
        if ($this->class_from && $this->class_to) {
            return "Class {$this->class_from}–{$this->class_to}";
        }
        if ($this->class_from) {
            return "Class {$this->class_from}+";
        }
        return 'All classes';
    }

    /**
     * Check if this rule matches a borrower (and optionally a book's catalog category).
     */
    public function matchesBorrower(User $user, ?LibraryBook $book = null): bool
    {
        // Catalog category filter
        if ($this->library_category_id !== null && $book !== null) {
            if ($book->library_category_id !== $this->library_category_id) {
                return false;
            }
        }

        // Patron type filter
        if ($this->patron_type) {
            if ($this->resolvePatronType($user) !== $this->patron_type) {
                return false;
            }
        }

        if ($this->organization_slug || $this->department_name) {
            $member = $user->student;
            $organization = $member?->organization ?: $user->organizationSlug();
            $department = $member?->stream ?: ($member?->program ?: $user->departmentName());

            if (($this->organization_slug && $organization !== $this->organization_slug)
                || ($this->department_name && $department !== $this->department_name)) {
                return false;
            }
        }

        // Class range filter (only meaningful for students)
        if ($this->class_from !== null || $this->class_to !== null) {
            $grade = $this->extractGrade($user);
            if (! $grade) {
                return false;
            }
            if ($this->class_from !== null && $grade < $this->class_from) {
                return false;
            }
            if ($this->class_to !== null && $grade > $this->class_to) {
                return false;
            }
        }

        return true;
    }

    public function matchesMember(Student $member, ?LibraryBook $book = null): bool
    {
        if ($this->library_category_id !== null && $book !== null
            && $book->library_category_id !== $this->library_category_id) {
            return false;
        }

        if ($this->patron_type && $this->patron_type !== $member->member_type) {
            return false;
        }

        if (($this->organization_slug && $member->organization !== $this->organization_slug)
            || ($this->department_name && ($member->stream ?: $member->program) !== $this->department_name)) {
            return false;
        }

        if ($this->class_from !== null || $this->class_to !== null) {
            preg_match('/(?<!\d)(\d{1,2})(?!\d)/', (string) ($member->stream ?: $member->program), $match);
            $grade = isset($match[1]) ? (int) $match[1] : null;
            if (! $grade
                || ($this->class_from !== null && $grade < $this->class_from)
                || ($this->class_to !== null && $grade > $this->class_to)) {
                return false;
            }
        }

        return true;
    }

    private function resolvePatronType(User $user): string
    {
        $roles = $user->roles->pluck('name');
        if ($roles->contains('student')) {
            return 'student';
        }
        if ($roles->contains('teacher')) {
            return 'teacher';
        }
        return 'staff';
    }

    private function extractGrade(User $user): ?int
    {
        $raw = $user->class_grade ?? '';
        $n   = (int) filter_var($raw, FILTER_SANITIZE_NUMBER_INT);
        return $n > 0 ? $n : null;
    }
}
