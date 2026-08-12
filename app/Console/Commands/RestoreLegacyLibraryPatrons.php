<?php

namespace App\Console\Commands;

use App\Models\Card\Student;
use App\Models\LibraryLoan;
use App\Support\MySqlDumpReader;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RestoreLegacyLibraryPatrons extends Command
{
    protected $signature = 'library:restore-legacy-patrons
        {file : Absolute path to the legacy library SQL dump}
        {--apply : Create missing HR members and restore their loan links}';

    protected $description = 'Create missing 2082 Class 12 Science and staff patrons in HR, then restore library allocations';

    public function handle(MySqlDumpReader $reader): int
    {
        $file = (string) $this->argument('file');
        $apply = (bool) $this->option('apply');

        if (! is_file($file) || ! is_readable($file)) {
            $this->components->error("SQL file is not readable: {$file}");
            return self::FAILURE;
        }

        $sql = (string) file_get_contents($file);
        $patrons = collect($reader->rows($sql, 'students'));
        $issues = collect($reader->rows($sql, 'issues'));
        $departments = collect($reader->rows($sql, 'departments'))->keyBy(fn (array $row) => (int) $row['id']);
        $categories = collect($reader->rows($sql, 'categories'))->keyBy(fn (array $row) => (int) $row['id']);
        $members = Student::query()->get();
        $toCreate = collect();
        $alreadyPresent = 0;

        foreach ($patrons as $patron) {
            $department = $departments->get((int) $patron['faculty_id'], []);
            $category = strtolower((string) ($categories->get((int) $patron['category_id'])['name'] ?? ''));
            $profile = $this->targetProfile($patron, $department, $category);

            if (! $profile) {
                continue;
            }

            $existing = $this->findExisting($patron, $members, $profile['organization']);
            if ($existing) {
                $alreadyPresent++;
                continue;
            }

            $toCreate->push(['patron' => $patron, 'profile' => $profile]);
        }

        $loanCount = $issues
            ->whereIn('student_id', $toCreate->pluck('patron.id')->map(fn ($id) => (int) $id))
            ->count();

        $breakdown = $toCreate
            ->groupBy(fn (array $item) => $item['profile']['member_type'].' · '.$item['profile']['organization'].' · '.($item['profile']['program'] ?: $item['profile']['stream']))
            ->map->count()
            ->sortKeys()
            ->map(fn (int $count, string $group) => [$group, $count])
            ->values()
            ->all();

        $this->table(['Check', 'Count'], [
            ['Missing HR members ready to create', $toCreate->count()],
            ['Eligible patrons already in HR', $alreadyPresent],
            ['Library allocations/history to relink', $loanCount],
        ]);
        if ($breakdown !== []) {
            $this->table(['HR group', 'Members'], $breakdown);
        }

        if (! $apply) {
            $this->components->info('Dry run only. Rerun with --apply to create these HR members and restore allocations.');
            return self::SUCCESS;
        }

        $createdByLegacyId = [];
        $restoredLoans = 0;

        DB::transaction(function () use ($toCreate, $issues, &$createdByLegacyId, &$restoredLoans): void {
            foreach ($toCreate as $item) {
                $patron = $item['patron'];
                $profile = $item['profile'];
                [$firstName, $middleName, $lastName] = $this->splitName((string) $patron['name']);

                $member = Student::create([
                    'organization' => $profile['organization'],
                    'member_type' => $profile['member_type'],
                    'roll_number' => trim((string) $patron['roll_no']),
                    'first_name' => $firstName,
                    'middle_name' => $middleName,
                    'last_name' => $lastName,
                    'email' => $patron['email'] ?: null,
                    'mobile' => $patron['contact_number'] ?: null,
                    'address_en' => $patron['address'] ?: null,
                    'municipality' => $patron['address'] ?: null,
                    'country' => 'Nepal',
                    'program' => $profile['program'],
                    'stream' => $profile['stream'],
                    'section' => $profile['section'],
                    'batch' => $profile['batch'],
                    'designation' => $profile['designation'],
                    'library_id' => trim((string) $patron['roll_no']),
                    'has_library_card' => true,
                    'created_at' => $patron['created_at'],
                    'updated_at' => $patron['updated_at'],
                ]);

                $createdByLegacyId[(int) $patron['id']] = $member;
            }

            foreach ($issues as $issue) {
                $member = $createdByLegacyId[(int) $issue['student_id']] ?? null;
                if (! $member) {
                    continue;
                }

                $updated = LibraryLoan::whereNull('student_id')
                    ->where('remarks', 'like', 'Legacy library issue #'.$issue['id'].'%')
                    ->update(['student_id' => $member->id]);
                $restoredLoans += $updated;
            }
        });

        $this->components->info("Created {$toCreate->count()} HR members and restored {$restoredLoans} library allocation/history links.");
        return self::SUCCESS;
    }

    /** @return array{organization:string,member_type:string,program:?string,stream:?string,section:?string,batch:?string,designation:?string}|null */
    private function targetProfile(array $patron, array $department, string $category): ?array
    {
        $departmentName = strtolower(trim((string) ($department['name'] ?? '')));
        $departmentCode = strtolower(trim((string) ($department['code'] ?? '')));
        if (str_contains($category, 'student')
            && (str_contains($departmentName, 'science') || str_contains($departmentName, 'management'))) {
            $stream = str_contains($departmentName, 'management') ? 'Management' : 'Science';
            $class = '12 '.$stream;

            return [
                'organization' => 'school',
                'member_type' => 'student',
                'program' => $class,
                'stream' => $class,
                'section' => 'ALL',
                'batch' => '2082',
                'designation' => null,
            ];
        }

        $memberType = str_contains($category, 'teacher') ? 'teacher' : (str_contains($category, 'staff') ? 'staff' : null);
        if (! $memberType) {
            return null;
        }

        [$organization, $stream] = $this->organizationAndStream($departmentCode, $departmentName);

        return [
            'organization' => $organization,
            'member_type' => $memberType,
            'program' => null,
            'stream' => $stream,
            'section' => null,
            'batch' => null,
            'designation' => $memberType === 'teacher' ? 'Teacher' : 'Staff',
        ];
    }

    /** @return array{string, ?string} */
    private function organizationAndStream(string $code, string $name): array
    {
        return match (true) {
            in_array($code, ['bba', 'bca'], true) => ['NCIS', strtoupper($code)],
            str_contains($code, 'csit') || str_contains($name, 'csit') => ['college', 'B.Sc.CSIT'],
            $code === 'bbs' || $name === 'bbs' => ['college', 'BBS'],
            str_contains($name, 'hotel') => ['school', 'Hotel Management'],
            str_contains($name, 'management') => ['school', 'Management'],
            str_contains($name, 'science') => ['school', 'Science'],
            default => ['college', $name !== '' ? ucwords($name) : null],
        };
    }

    private function findExisting(array $patron, Collection $members, string $organization): ?Student
    {
        $roll = $this->normalize((string) $patron['roll_no']);
        $name = $this->normalize((string) $patron['name']);
        $candidates = $members->where('organization', $organization)->filter(
            fn (Student $member) => $this->normalize((string) $member->roll_number) === $roll
        );

        if ($candidates->count() === 1) {
            return $candidates->first();
        }

        $byName = $members->where('organization', $organization)->filter(
            fn (Student $member) => $this->normalize($member->full_name) === $name
        );

        return $byName->count() === 1 ? $byName->first() : null;
    }

    /** @return array{string, ?string, string} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($parts) === 1) {
            return [$parts[0], null, '-'];
        }
        $first = array_shift($parts);
        $last = array_pop($parts);
        return [$first, $parts ? implode(' ', $parts) : null, $last];
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(preg_replace('/[^\pL\pN]+/u', '', trim($value)) ?? '');
    }
}
