<?php

namespace App\Console\Commands;

use App\Models\Card\Department;
use App\Models\Card\Organization;
use App\Models\Card\Section;
use App\Models\Card\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeSchoolClasses extends Command
{
    protected $signature = 'students:normalize-school-classes
        {--apply : Create the class masters and update student records}';

    protected $description = 'Replace ambiguous school streams with Class 11/12 Science, Management, and Hotel Management';

    private const TRACKS = ['Science', 'Management', 'Hotel Management'];

    public function handle(): int
    {
        $students = Student::query()
            ->where('organization', 'school')
            ->where('member_type', 'student')
            ->where(fn ($query) => $query->whereNull('stream')->orWhere('stream', '!=', 'Graduated'))
            ->get();

        $assignments = $students
            ->mapWithKeys(fn (Student $student) => [$student->id => $this->classFor($student)])
            ->filter();
        $unresolved = $students->reject(fn (Student $student) => $assignments->has($student->id));

        $this->table(['Class', 'Students'], $assignments
            ->countBy()
            ->sortKeys(SORT_NATURAL)
            ->map(fn (int $count, string $class) => [$class, $count])
            ->values()
            ->all());
        $this->line('Students ready to assign: '.$assignments->count());
        $this->line('Students needing manual class assignment: '.$unresolved->count());

        if ($unresolved->isNotEmpty()) {
            $this->table(['ID', 'Roll', 'Student', 'Batch'], $unresolved
                ->map(fn (Student $student) => [
                    $student->id,
                    $student->roll_number,
                    $student->full_name,
                    $student->batch ?: '—',
                ])
                ->all());
        }

        if (! $this->option('apply')) {
            $this->components->info('Dry run only. Rerun with --apply to make these changes.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($assignments): void {
            $school = Organization::query()->where('slug', 'school')->firstOrFail();

            foreach (self::TRACKS as $track) {
                $this->ensureClassMasters($school, $track);
            }

            foreach ($assignments as $studentId => $class) {
                Student::query()->whereKey($studentId)->update([
                    'stream' => $class,
                    'program' => $class,
                    'section' => DB::raw("COALESCE(NULLIF(section, ''), 'ALL')"),
                ]);
            }
        });

        $this->components->info('School class masters and student assignments were normalized successfully.');

        return self::SUCCESS;
    }

    private function ensureClassMasters(Organization $school, string $track): void
    {
        $class11Name = '11 '.$track;
        $class12Name = '12 '.$track;
        $generic = Department::query()
            ->where('organization_id', $school->id)
            ->where('name', $track)
            ->first();
        $class11 = Department::query()
            ->where('organization_id', $school->id)
            ->where('name', $class11Name)
            ->first();

        if (! $class11 && $generic) {
            $generic->update(['name' => $class11Name, 'is_active' => true]);
            $class11 = $generic;
        } else {
            $class11 ??= Department::create([
                'organization_id' => $school->id,
                'name' => $class11Name,
                'is_active' => true,
            ]);
        }

        if ($generic && ! $generic->is($class11)) {
            $generic->sections()->get()->each(function (Section $section) use ($class11): void {
                Section::firstOrCreate(
                    ['department_id' => $class11->id, 'name' => $section->name],
                    ['is_active' => $section->is_active]
                );
            });
            $generic->delete();
        }

        $class12 = Department::firstOrCreate(
            ['organization_id' => $school->id, 'name' => $class12Name],
            ['is_active' => true]
        );
        $class11->update(['is_active' => true]);
        $class12->update(['is_active' => true]);

        foreach ([$class11, $class12] as $class) {
            Section::firstOrCreate(
                ['department_id' => $class->id, 'name' => 'ALL'],
                ['is_active' => true]
            );
        }
    }

    private function classFor(Student $student): ?string
    {
        $source = trim(($student->program ?? '').' '.($student->stream ?? ''));
        $track = $this->trackFrom($source);
        if (! $track) {
            return null;
        }

        $grade = null;
        if (preg_match('/(?<!\d)(11|12)(?!\d)/', $source, $match)) {
            $grade = (int) $match[1];
        } elseif (in_array((string) $student->batch, ['2082', '2083'], true)) {
            $grade = (string) $student->batch === '2082' ? 12 : 11;
        } elseif (preg_match('/(?:^|[-\/])(2082|2083)$/', trim((string) $student->roll_number), $match)) {
            $grade = $match[1] === '2082' ? 12 : 11;
        }

        return $grade ? $grade.' '.$track : null;
    }

    private function trackFrom(string $value): ?string
    {
        $value = strtolower($value);

        return match (true) {
            str_contains($value, 'hotel'), preg_match('/\bhm\b/', $value) === 1 => 'Hotel Management',
            str_contains($value, 'management'), str_contains($value, 'mgmt') => 'Management',
            str_contains($value, 'science') => 'Science',
            default => null,
        };
    }
}
