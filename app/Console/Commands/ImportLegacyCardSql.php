<?php

namespace App\Console\Commands;

use App\Models\Card\Department;
use App\Models\Card\MemberType;
use App\Models\Card\Organization;
use App\Models\Card\Section;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class ImportLegacyCardSql extends Command
{
    protected $signature = 'card:import-legacy-sql
        {file : Absolute path to the legacy SQL dump}
        {--source-organization=college : Only import records with this legacy organization slug}
        {--target-organization=college : Organization slug to use in this ERP}
        {--apply : Write records; without this option the command is a dry run}';

    protected $description = 'Safely import compatible member/card records from a legacy SQL dump';

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        $sourceOrganization = trim((string) $this->option('source-organization'));
        $targetOrganization = trim((string) $this->option('target-organization'));
        $apply = (bool) $this->option('apply');

        if (! is_file($file) || ! is_readable($file)) {
            $this->components->error("SQL file is not readable: {$file}");

            return self::FAILURE;
        }

        if ($sourceOrganization === '' || $targetOrganization === '') {
            $this->components->error('Source and target organization slugs cannot be empty.');

            return self::FAILURE;
        }

        try {
            $rows = $this->studentRows((string) file_get_contents($file));
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $sourceRows = collect($rows)
            ->filter(fn (array $row) => (string) ($row['organization'] ?? '') === $sourceOrganization)
            ->values();

        if ($sourceRows->isEmpty()) {
            $this->components->warn("No student rows found for organization '{$sourceOrganization}'.");

            return self::SUCCESS;
        }

        $allowedColumns = array_flip(array_diff(Schema::getColumnListing('students'), [
            'id', 'user_id', 'department_id', 'section_id',
        ]));
        $seen = [];
        $valid = collect();
        $invalid = 0;
        $duplicates = 0;
        $missingPhotos = 0;

        foreach ($sourceRows as $sourceRow) {
            $row = array_intersect_key($sourceRow, $allowedColumns);
            $row['organization'] = $targetOrganization;
            $row['member_type'] = strtolower((string) ($row['member_type'] ?? 'student'));
            $row['country'] = $row['country'] ?: 'Nepal';

            if ($targetOrganization === 'school' && $row['member_type'] === 'student') {
                $class = $this->schoolClassName($row);
                if ($class) {
                    $row['stream'] = $class;
                    $row['program'] = $class;
                    $row['section'] = $row['section'] ?: 'ALL';
                }
            }

            if (! in_array($row['member_type'], ['student', 'teacher', 'staff'], true)
                || trim((string) ($row['roll_number'] ?? '')) === ''
                || trim((string) ($row['first_name'] ?? '')) === ''
                || trim((string) ($row['last_name'] ?? '')) === '') {
                $invalid++;
                continue;
            }

            $key = implode('|', [
                $targetOrganization,
                (string) ($row['stream'] ?? ''),
                (string) ($row['section'] ?? ''),
                (string) $row['roll_number'],
            ]);

            if (isset($seen[$key]) || DB::table('students')
                ->where('organization', $targetOrganization)
                ->where('stream', $row['stream'] ?? null)
                ->where('section', $row['section'] ?? null)
                ->where('roll_number', $row['roll_number'])
                ->exists()) {
                $duplicates++;
                continue;
            }

            $seen[$key] = true;

            if (! empty($row['photo']) && ! is_file(public_path(ltrim((string) $row['photo'], '/')))) {
                $missingPhotos++;
            }

            $valid->push($row);
        }

        $summary = [
            ['Legacy student rows', (string) count($rows)],
            ["Rows for '{$sourceOrganization}'", (string) $sourceRows->count()],
            ['Ready to import', (string) $valid->count()],
            ['Existing/duplicate rows skipped', (string) $duplicates],
            ['Invalid rows skipped', (string) $invalid],
            ['Referenced photos not yet copied', (string) $missingPhotos],
        ];

        $this->table(['Check', 'Count'], $summary);

        $breakdown = $valid
            ->groupBy(fn (array $row) => ($row['member_type'] ?? 'student').' · '.($row['stream'] ?: 'No department'))
            ->map->count()
            ->sortKeys()
            ->map(fn (int $count, string $group) => [$group, $count])
            ->values()
            ->all();

        if ($breakdown !== []) {
            $this->table(['Group', 'Ready'], $breakdown);
        }

        if (! $apply) {
            $this->components->info('Dry run only. Copy the photos folder, then rerun with --apply to import.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($valid, $targetOrganization): void {
            $profile = $this->organizationProfile($targetOrganization);
            $organization = Organization::firstOrCreate(
                ['slug' => $targetOrganization],
                [
                    'name' => $profile['name'],
                    'type' => $profile['type'],
                    'is_active' => true,
                ]
            );

            foreach (['Student', 'Teacher', 'Staff'] as $memberType) {
                MemberType::firstOrCreate(
                    ['organization_id' => $organization->id, 'name' => $memberType],
                    ['is_active' => true]
                );
            }

            foreach ($valid->pluck('stream')->filter()->unique()->sort() as $stream) {
                $department = Department::firstOrCreate(
                    ['organization_id' => $organization->id, 'name' => $stream],
                    [
                        'university' => $profile['university'],
                        'university_college' => $profile['university_college'],
                        'is_active' => true,
                    ]
                );

                $sections = $valid
                    ->where('stream', $stream)
                    ->pluck('section')
                    ->filter()
                    ->unique();

                foreach ($sections as $section) {
                    Section::firstOrCreate(
                        ['department_id' => $department->id, 'name' => $section],
                        ['is_active' => true]
                    );
                }
            }

            foreach ($valid->chunk(200) as $chunk) {
                DB::table('students')->insert($chunk->all());
            }
        });

        $this->components->info("Imported {$valid->count()} legacy card records into '{$targetOrganization}'.");

        return self::SUCCESS;
    }

    /** @return array{name: string, type: string, university: ?string, university_college: ?string} */
    private function organizationProfile(string $slug): array
    {
        return match (strtolower($slug)) {
            'college' => [
                'name' => 'Godawari College',
                'type' => 'college',
                'university' => 'TRIBHUVAN UNIVERSITY',
                'university_college' => 'Godawari College',
            ],
            'school' => [
                'name' => 'Sushma Secondary School',
                'type' => 'school',
                'university' => null,
                'university_college' => null,
            ],
            'ncis' => [
                'name' => 'National College of Integrated Studies',
                'type' => 'college',
                'university' => 'Rajarshi Janak University',
                'university_college' => 'National College of Integrated Studies',
            ],
            default => [
                'name' => Str::headline($slug),
                'type' => 'other',
                'university' => null,
                'university_college' => null,
            ],
        };
    }

    private function schoolClassName(array $row): ?string
    {
        $source = strtolower(trim(($row['program'] ?? '').' '.($row['stream'] ?? '')));
        $track = match (true) {
            str_contains($source, 'hotel'), preg_match('/\bhm\b/', $source) === 1 => 'Hotel Management',
            str_contains($source, 'management'), str_contains($source, 'mgmt') => 'Management',
            str_contains($source, 'science') => 'Science',
            default => null,
        };

        if (! $track) {
            return null;
        }

        if (preg_match('/(?<!\d)(11|12)(?!\d)/', $source, $match)) {
            return $match[1].' '.$track;
        }

        $batch = (string) ($row['batch'] ?? '');
        if (in_array($batch, ['2082', '2083'], true)) {
            return ($batch === '2082' ? '12 ' : '11 ').$track;
        }

        $roll = trim((string) ($row['roll_number'] ?? ''));
        if (preg_match('/(?:^|[-\/])(2082|2083)$/', $roll, $match)) {
            return ($match[1] === '2082' ? '12 ' : '11 ').$track;
        }

        return null;
    }

    /** @return array<int, array<string, mixed>> */
    private function studentRows(string $sql): array
    {
        if (! preg_match_all('/INSERT\s+INTO\s+`students`\s*\((.*?)\)\s*VALUES\s*(.*?);/si', $sql, $statements, PREG_SET_ORDER)) {
            throw new RuntimeException('No INSERT statements for the students table were found.');
        }

        $rows = [];

        foreach ($statements as $statement) {
            preg_match_all('/`([^`]+)`/', $statement[1], $columnMatches);
            $columns = $columnMatches[1];

            foreach ($this->parseValueTuples($statement[2]) as $values) {
                if (count($columns) !== count($values)) {
                    throw new RuntimeException('A students INSERT row does not match its column count.');
                }

                $rows[] = array_combine($columns, $values);
            }
        }

        return $rows;
    }

    /** @return array<int, array<int, mixed>> */
    private function parseValueTuples(string $valuesSql): array
    {
        $tuples = [];
        $tuple = [];
        $token = '';
        $inTuple = false;
        $inString = false;
        $escaped = false;
        $length = strlen($valuesSql);

        for ($index = 0; $index < $length; $index++) {
            $char = $valuesSql[$index];

            if (! $inTuple) {
                if ($char === '(') {
                    $inTuple = true;
                    $tuple = [];
                    $token = '';
                }
                continue;
            }

            if ($inString) {
                $token .= $char;
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === "'") {
                    $inString = false;
                }
                continue;
            }

            if ($char === "'") {
                $inString = true;
                $token .= $char;
            } elseif ($char === ',') {
                $tuple[] = $this->decodeValue($token);
                $token = '';
            } elseif ($char === ')') {
                $tuple[] = $this->decodeValue($token);
                $tuples[] = $tuple;
                $tuple = [];
                $token = '';
                $inTuple = false;
            } else {
                $token .= $char;
            }
        }

        if ($inTuple || $inString) {
            throw new RuntimeException('The students SQL values are incomplete or malformed.');
        }

        return $tuples;
    }

    private function decodeValue(string $value): mixed
    {
        $value = trim($value);

        if (strcasecmp($value, 'NULL') === 0) {
            return null;
        }

        if (strlen($value) >= 2 && $value[0] === "'" && $value[strlen($value) - 1] === "'") {
            $value = substr($value, 1, -1);

            return strtr($value, [
                "\\0" => "\0",
                "\\n" => "\n",
                "\\r" => "\r",
                "\\Z" => "\x1a",
                "\\'" => "'",
                '\\"' => '"',
                "\\\\" => "\\",
            ]);
        }

        return is_numeric($value) ? $value + 0 : $value;
    }
}
