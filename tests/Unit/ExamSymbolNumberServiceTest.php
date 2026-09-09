<?php

namespace Tests\Unit;

use App\Services\ExamSymbolNumberService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExamSymbolNumberServiceTest extends TestCase
{
    public function test_school_numbers_use_class_prefix_and_shared_sequence(): void
    {
        $students = collect([
            (object) ['id' => 3, 'school_class' => 12],
            (object) ['id' => 1, 'school_class' => 11],
            (object) ['id' => 2, 'school_class' => 11],
            (object) ['id' => 4, 'school_class' => 12],
        ]);
        $this->assertSame([1 => 110001, 2 => 110002, 3 => 120003, 4 => 120004], (new ExamSymbolNumberService)->numbers($students, true)->all());
    }

    public function test_college_numbers_remain_sequential(): void
    {
        $students = collect([(object) ['id' => 1], (object) ['id' => 2]]);
        $this->assertSame([1 => 200, 2 => 201], (new ExamSymbolNumberService)->numbers($students, false, 200)->all());
    }

    public function test_missing_school_class_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        (new ExamSymbolNumberService)->numbers(collect([(object) ['id' => 1, 'school_class' => null]]), true);
    }

    public function test_sequence_cannot_overflow_the_class_prefix(): void
    {
        $this->expectException(ValidationException::class);
        (new ExamSymbolNumberService)->numbers(collect([(object) ['id' => 1, 'school_class' => 11], (object) ['id' => 2, 'school_class' => 12]]), true, 9999);
    }
}
