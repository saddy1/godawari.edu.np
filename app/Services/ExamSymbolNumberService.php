<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ExamSymbolNumberService
{
    public function numbers(Collection $students, bool $school, int $start = 1): Collection
    {
        if ($school && $students->contains(fn ($student) => ! in_array($student->school_class, [11, 12], true))) {
            throw ValidationException::withMessages(['start_number' => 'Assign Class 11 or Class 12 to every school stream in student settings first.']);
        }
        if ($school && $start + $students->count() - 1 > 9999) {
            throw ValidationException::withMessages(['start_number' => 'The shared school sequence cannot exceed 9999.']);
        }
        $ordered = $school ? $students->sortBy('school_class', SORT_NUMERIC) : $students;
        return $ordered->values()->mapWithKeys(fn ($student, $index) => [
            $student->id => ($school ? $student->school_class * 10000 : 0) + $start + $index,
        ]);
    }
}
