<?php

namespace App\Console\Commands;

use App\Models\Card\Student;
use App\Models\LibraryBook;
use App\Models\LibraryBookCopy;
use App\Models\LibraryCategory;
use App\Models\LibraryLoan;
use App\Support\MySqlDumpReader;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ImportLegacyLibrarySql extends Command
{
    protected $signature = 'library:import-legacy-sql
        {file : Absolute path to the legacy library SQL dump}
        {--apply : Write catalogue, copies, patrons, and loan history}
        {--report= : CSV path for unmatched patrons}';

    protected $description = 'Import a legacy Godawari library dump and reconcile patrons with ERP HR/card members';

    public function handle(MySqlDumpReader $reader): int
    {
        $file = (string) $this->argument('file');
        $apply = (bool) $this->option('apply');
        $report = (string) ($this->option('report') ?: storage_path('app/import-reports/library-unmatched.csv'));

        if (! is_file($file) || ! is_readable($file)) {
            $this->components->error("SQL file is not readable: {$file}");
            return self::FAILURE;
        }

        try {
            $sql = (string) file_get_contents($file);
            $legacy = [
                'categories' => $reader->rows($sql, 'item_categories'),
                'departments' => $reader->rows($sql, 'departments'),
                'books' => $reader->rows($sql, 'books'),
                'copies' => $reader->rows($sql, 'books_items'),
                'patron_categories' => $reader->rows($sql, 'categories'),
                'patrons' => $reader->rows($sql, 'students'),
                'issues' => $reader->rows($sql, 'issues'),
            ];
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());
            return self::FAILURE;
        }

        $hrMembers = Student::query()
            ->get(['id', 'organization', 'member_type', 'roll_number', 'first_name', 'middle_name', 'last_name', 'email', 'mobile', 'library_id']);
        $departments = collect($legacy['departments'])->keyBy(fn (array $row) => (int) $row['id']);
        $patronCategories = collect($legacy['patron_categories'])->keyBy(fn (array $row) => (int) $row['id']);
        $matches = [];
        $unmatched = [];
        $ambiguous = [];
        $matchedBy = ['roll' => 0, 'email' => 0, 'name' => 0];

        foreach ($legacy['patrons'] as $patron) {
            $match = $this->matchPatron($patron, $hrMembers);
            $legacyId = (int) $patron['id'];

            if ($match['status'] === 'matched') {
                $matches[$legacyId] = $match['member'];
                $matchedBy[$match['method']]++;
            } else {
                $row = $this->reviewRow($patron, $match, $departments, $patronCategories);
                $unmatched[] = $row;
                if ($match['status'] === 'ambiguous') {
                    $ambiguous[] = $row;
                }
            }
        }

        $this->writeReport($report, $unmatched);

        $matchedIssueCount = collect($legacy['issues'])->filter(fn (array $issue) => isset($matches[(int) $issue['student_id']]))->count();
        $summary = [
            ['Books', count($legacy['books'])],
            ['Physical copies/barcodes', count($legacy['copies'])],
            ['Library patrons', count($legacy['patrons'])],
            ['Matched by roll number', $matchedBy['roll']],
            ['Matched by email', $matchedBy['email']],
            ['Matched by exact unique name', $matchedBy['name']],
            ['Unmatched patrons', count($unmatched)],
            ['Ambiguous patrons', count($ambiguous)],
            ['Issue/return records', count($legacy['issues'])],
            ['Issues linked to an ERP member', $matchedIssueCount],
        ];
        $this->table(['Legacy data', 'Count'], $summary);
        $this->line("Unmatched review report: {$report}");

        if (! $apply) {
            $this->components->info('Dry run only. Review the CSV, then rerun with --apply.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($legacy, $matches, $departments, $patronCategories): void {
            $categoryMap = $this->importCategories($legacy['categories']);
            $bookMap = $this->importBooks($legacy['books'], $categoryMap, $departments);
            $copyMap = $this->importCopies($legacy['copies'], $bookMap);
            $this->assignPatrons($legacy['patrons'], $matches, $patronCategories);
            $this->importLoans($legacy['issues'], $legacy['patrons'], $matches, $copyMap, $patronCategories);
        });

        $this->components->info('Legacy library catalogue, copies, patrons, and loan history imported successfully.');
        return self::SUCCESS;
    }

    /** @return array{status:string, method?:string, member?:Student, candidates:Collection<int, Student>} */
    private function matchPatron(array $patron, Collection $members): array
    {
        $roll = $this->normalizeIdentifier((string) ($patron['roll_no'] ?? ''));
        $name = $this->normalizeName((string) ($patron['name'] ?? ''));
        $email = strtolower(trim((string) ($patron['email'] ?? '')));

        $byRoll = $roll === '' ? collect() : $members->filter(
            fn (Student $member) => $this->normalizeIdentifier((string) $member->roll_number) === $roll
        )->values();
        if ($byRoll->count() === 1) {
            return ['status' => 'matched', 'method' => 'roll', 'member' => $byRoll->first(), 'candidates' => $byRoll];
        }
        if ($byRoll->count() > 1) {
            $sameName = $byRoll->filter(fn (Student $member) => $this->normalizeName($member->full_name) === $name)->values();
            if ($sameName->count() === 1) {
                return ['status' => 'matched', 'method' => 'roll', 'member' => $sameName->first(), 'candidates' => $sameName];
            }
            return ['status' => 'ambiguous', 'candidates' => $byRoll];
        }

        $byEmail = $email === '' ? collect() : $members->filter(
            fn (Student $member) => strtolower(trim((string) $member->email)) === $email
        )->values();
        if ($byEmail->count() === 1) {
            return ['status' => 'matched', 'method' => 'email', 'member' => $byEmail->first(), 'candidates' => $byEmail];
        }

        $byName = $name === '' ? collect() : $members->filter(
            fn (Student $member) => $this->normalizeName($member->full_name) === $name
        )->values();
        if ($byName->count() === 1) {
            return ['status' => 'matched', 'method' => 'name', 'member' => $byName->first(), 'candidates' => $byName];
        }
        if ($byName->count() > 1) {
            return ['status' => 'ambiguous', 'candidates' => $byName];
        }

        return ['status' => 'unmatched', 'candidates' => $this->closestCandidates($name, $members)];
    }

    private function closestCandidates(string $name, Collection $members): Collection
    {
        if ($name === '') {
            return collect();
        }

        return $members
            ->map(fn (Student $member) => ['member' => $member, 'distance' => levenshtein($name, $this->normalizeName($member->full_name))])
            ->sortBy('distance')
            ->take(3)
            ->pluck('member')
            ->values();
    }

    private function reviewRow(array $patron, array $match, Collection $departments, Collection $categories): array
    {
        $candidates = $match['candidates'] ?? collect();
        $first = $candidates->get(0);
        $second = $candidates->get(1);

        return [
            'status' => $match['status'],
            'legacy_id' => $patron['id'],
            'legacy_roll' => $patron['roll_no'],
            'legacy_name' => $patron['name'],
            'legacy_email' => $patron['email'],
            'legacy_phone' => $patron['contact_number'],
            'legacy_department' => $departments->get((int) $patron['faculty_id'])['name'] ?? '',
            'legacy_type' => $categories->get((int) $patron['category_id'])['name'] ?? '',
            'suggestion_1_id' => $first?->id,
            'suggestion_1_roll' => $first?->roll_number,
            'suggestion_1_name' => $first?->full_name,
            'suggestion_2_id' => $second?->id,
            'suggestion_2_roll' => $second?->roll_number,
            'suggestion_2_name' => $second?->full_name,
        ];
    }

    private function writeReport(string $path, array $rows): void
    {
        File::ensureDirectoryExists(dirname($path));
        $handle = fopen($path, 'wb');
        $headers = ['status','legacy_id','legacy_roll','legacy_name','legacy_email','legacy_phone','legacy_department','legacy_type','suggestion_1_id','suggestion_1_roll','suggestion_1_name','suggestion_2_id','suggestion_2_roll','suggestion_2_name'];
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn (string $header) => $row[$header] ?? '', $headers));
        }
        fclose($handle);
    }

    /** @return array<int, int> */
    private function importCategories(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $category = LibraryCategory::firstOrCreate(
                ['name' => trim((string) $row['name'])],
                ['description' => 'Imported legacy library category '.$row['code']]
            );
            $map[(int) $row['id']] = $category->id;
        }
        return $map;
    }

    /** @return array<int, int> */
    private function importBooks(array $rows, array $categoryMap, Collection $departments): array
    {
        $map = [];
        foreach ($rows as $row) {
            $isbn = trim((string) ($row['isbn'] ?? '')) ?: null;
            $book = $isbn ? LibraryBook::where('isbn', $isbn)->first() : null;
            $book ??= LibraryBook::where('title', $row['title'])->where('author', $row['author'])->first();
            $department = $departments->get((int) $row['faculty_id']);

            if (! $book) {
                $descriptionParts = array_filter([
                    $row['description'] ?? null,
                    ! empty($row['remarks']) ? 'Legacy remarks: '.$row['remarks'] : null,
                ]);
                $book = LibraryBook::create([
                    'library_category_id' => $categoryMap[(int) $row['category_id']] ?? null,
                    'title' => $row['title'],
                    'author' => $row['author'] ?: null,
                    'isbn' => $isbn,
                    'publisher' => $row['publisher'] ?: null,
                    'publication_year' => $this->validPublicationYear($row['publication_year'] ?? null),
                    'edition' => $row['edition'] ?: null,
                    'price' => $row['price'],
                    'pages' => $row['pages'],
                    'description' => $descriptionParts ? implode("\n", $descriptionParts) : null,
                    'source' => $row['source'] ?: null,
                    'shelf_location' => $department['code'] ?? null,
                    'is_active' => true,
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ]);
            }
            $map[(int) $row['id']] = $book->id;
        }
        return $map;
    }

    /** @return array<int, int> */
    private function importCopies(array $rows, array $bookMap): array
    {
        $map = [];
        foreach ($rows as $row) {
            if (! isset($bookMap[(int) $row['book_id']])) {
                continue;
            }
            $barcode = trim((string) $row['barcode']);
            $copy = LibraryBookCopy::firstOrCreate(
                ['accession_no' => $barcode],
                [
                    'library_book_id' => $bookMap[(int) $row['book_id']],
                    'barcode' => $barcode,
                    'status' => strtolower((string) $row['status']) === 'available' ? 'available' : 'issued',
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ]
            );
            $map[(int) $row['id']] = $copy->id;
        }
        return $map;
    }

    private function assignPatrons(array $patrons, array $matches, Collection $categories): void
    {
        foreach ($patrons as $patron) {
            $member = $matches[(int) $patron['id']] ?? null;
            if (! $member) {
                continue;
            }
            $type = strtolower((string) ($categories->get((int) $patron['category_id'])['name'] ?? ''));
            $updates = [
                'library_id' => trim((string) $patron['roll_no']),
                'has_library_card' => true,
            ];
            if ($member->member_type === 'student' && str_contains($type, 'staff')) {
                unset($updates['library_id']);
            }
            DB::table('students')->where('id', $member->id)->update($updates);
        }
    }

    private function importLoans(array $issues, array $patrons, array $matches, array $copyMap, Collection $categories): void
    {
        $patronsById = collect($patrons)->keyBy(fn (array $row) => (int) $row['id']);
        $activeCopyIds = [];

        foreach ($issues as $issue) {
            $copyId = $copyMap[(int) $issue['book_id']] ?? null;
            if (! $copyId) {
                continue;
            }
            if ($issue['status'] === 'issued') {
                $activeCopyIds[$copyId] = true;
            }
            $marker = 'Legacy library issue #'.$issue['id'];
            $existingLoan = LibraryLoan::where('remarks', 'like', $marker.'%')->first();
            if ($existingLoan) {
                if (! $existingLoan->student_id && $member) {
                    $existingLoan->update(['student_id' => $member->id]);
                }
                continue;
            }
            $patron = $patronsById->get((int) $issue['student_id'], []);
            $member = $matches[(int) $issue['student_id']] ?? null;
            $category = strtolower((string) ($categories->get((int) ($patron['category_id'] ?? 0))['name'] ?? 'patron'));
            $status = $issue['status'] === 'returned' ? 'returned' : 'issued';

            LibraryLoan::create([
                'library_book_copy_id' => $copyId,
                'student_id' => $member?->id,
                'borrower_name' => $patron['name'] ?? 'Unknown legacy patron',
                'borrower_identifier' => $patron['roll_no'] ?? null,
                'borrower_type' => $category,
                'issued_at' => $issue['issued_at'],
                'due_date' => $issue['due_date'],
                'returned_at' => $issue['returned_at'],
                'status' => $status,
                'fine_amount' => $issue['fine'] ?? 0,
                'fine_paid' => ! empty($issue['fine_paid_at']) ? ($issue['fine'] ?? 0) : 0,
                'remarks' => $marker.(! empty($issue['fine_clear_remarks']) ? ' · '.$issue['fine_clear_remarks'] : ''),
                'created_at' => $issue['created_at'],
                'updated_at' => $issue['updated_at'],
            ]);

        }

        $allImportedCopyIds = array_values(array_unique($copyMap));
        if ($allImportedCopyIds !== []) {
            DB::table('library_book_copies')
                ->whereIn('id', $allImportedCopyIds)
                ->whereIn('status', ['available', 'issued'])
                ->update(['status' => 'available']);
        }
        if ($activeCopyIds !== []) {
            DB::table('library_book_copies')
                ->whereIn('id', array_keys($activeCopyIds))
                ->update(['status' => 'issued']);
        }
    }

    private function normalizeIdentifier(string $value): string
    {
        return strtolower(preg_replace('/[^\pL\pN]+/u', '', trim($value)) ?? '');
    }

    private function normalizeName(string $value): string
    {
        $value = mb_strtolower(trim($value));
        return preg_replace('/[^\pL\pN]+/u', '', $value) ?? '';
    }

    private function validPublicationYear(mixed $year): ?int
    {
        $year = (int) $year;
        return $year >= 1000 && $year <= 9999 ? $year : null;
    }
}
