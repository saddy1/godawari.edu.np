<?php

namespace App\Services;

use Illuminate\Support\Collection;

class GradeService
{
    // Nepal's standard NEB grading scale (grade 9-12).
    private const SCALE = [
        ['min' => 90, 'grade' => 'A+', 'point' => 4.00],
        ['min' => 80, 'grade' => 'A', 'point' => 3.60],
        ['min' => 70, 'grade' => 'B+', 'point' => 3.20],
        ['min' => 60, 'grade' => 'B', 'point' => 2.80],
        ['min' => 50, 'grade' => 'C+', 'point' => 2.40],
        ['min' => 40, 'grade' => 'C', 'point' => 2.00],
        ['min' => 30, 'grade' => 'D+', 'point' => 1.60],
        ['min' => 20, 'grade' => 'D', 'point' => 1.20],
        ['min' => 0, 'grade' => 'NG', 'point' => 0.00],
    ];

    public function gradeFor(float $percentage): array
    {
        foreach (self::SCALE as $row) {
            if ($percentage >= $row['min']) {
                return ['grade' => $row['grade'], 'point' => $row['point']];
            }
        }

        return ['grade' => 'NG', 'point' => 0.00];
    }

    // Credit-hour-weighted GPA over rows shaped like ['credit_hour' => float, 'point' => float].
    public function gpaFor(Collection $rows): float
    {
        $creditHours = $rows->sum('credit_hour');
        if ($creditHours <= 0) return 0.0;

        $weighted = $rows->sum(fn ($row) => $row['credit_hour'] * $row['point']);

        return round($weighted / $creditHours, 2);
    }
}
