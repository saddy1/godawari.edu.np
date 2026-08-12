<?php

namespace App\Http\Controllers\Card;

use App\Http\Controllers\Controller;

use App\Models\Card\Organization;
use App\Models\Card\Student;
use App\Services\MemberAccountService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

class ImportController extends Controller
{
    private array $imageExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    // Maps normalized IEMIS/Excel column names → internal field names.
    // _fullname is a special marker: split on spaces into first/middle/last.
    private array $xlsxColumnMap = [
        'sn'                      => 'roll_number',
        's_n'                     => 'roll_number',
        'roll_number'             => 'roll_number',
        'student_id'              => 'registration_no',
        'registration_no'         => 'registration_no',
        'fullname'                => '_fullname',
        'full_name'               => '_fullname',
        'first_name'              => 'first_name',
        'middle_name'             => 'middle_name',
        'last_name'               => 'last_name',
        'gender'                  => 'gender',
        'father_name'             => 'father_name',
        'mother_name'             => 'mother_name',
        'dob'                     => 'dob_bs',       // IEMIS DOB is always in BS
        'dob_bs'                  => 'dob_bs',
        'permanent_address'       => 'address_en',
        'address_en'              => 'address_en',
        'guardian_name'           => 'guardian_name',
        'guardian_contact_number' => 'guardian_contact',
        'guardian_contact'        => 'guardian_contact',
        'mobile'                  => 'mobile',
        'email'                   => 'email',
        'citizenship_no'          => 'citizenship_no',
        'year'                    => 'batch',
        'batch'                   => 'batch',
        'program'                 => 'program',
        'designation'             => 'designation',
        'employment_type'         => 'employment_type',
    ];

    private function cleanupTempPhotoDirectory(?string $uuid): void
    {
        if (!$uuid) {
            return;
        }

        $tempDir = public_path("temp_photos/{$uuid}");

        if (File::isDirectory($tempDir)) {
            File::deleteDirectory($tempDir);
        }
    }

    // ── Import page ───────────────────────────────────────────────────────
    public function index()
    {
        return view('card.students.import', [
            'formOptions' => $this->buildImportOptions(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CSV IMPORT
    // ═══════════════════════════════════════════════════════════════════════

    public function previewCsv(Request $request)
    {
        $request->validate([
            'csv_file'     => 'required|file|mimes:csv,txt|max:5120',
            'organization' => 'required|string|max:100|exists:organizations,slug',
            'stream'       => 'nullable|string|max:100',
            'section'      => 'nullable|string|max:50',
            'valid_till'   => ['nullable', Rule::requiredIf($request->member_type === 'student'), 'date'],
            'member_type'  => 'required|in:student,teacher,staff',
            'create_learning_accounts' => 'boolean',
            'learning_password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $org       = $request->organization;
        $stream    = $request->stream ?: null;
        $section   = $request->section ?: null;
        $type      = $request->member_type;
        $validTill = $request->valid_till ?: null;
        $createLearningAccounts = $request->boolean('create_learning_accounts') && in_array($type, ['student', 'teacher'], true);
        $learningPassword = $request->input('learning_password');
        $learningPasswordToken = $learningPassword ? Crypt::encryptString($learningPassword) : null;

        $this->assertScopedImport($org, $stream, $section);

        $handle  = fopen($request->file('csv_file')->getRealPath(), 'r');
        $headers = array_map('trim', fgetcsv($handle));
        $headers = array_map(fn($h) => strtolower(str_replace([' ', '-'], '_', $h)), $headers);

        $required = ['first_name', 'last_name', 'roll_number'];
        $missing  = array_diff($required, $headers);
        if ($missing) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'CSV is missing columns: ' . implode(', ', $missing)]);
        }

        if (!in_array('dob', $headers) && !in_array('dob_bs', $headers)) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'CSV must have either a "dob" (AD date) or "dob_bs" (BS date) column.']);
        }

        $rows   = [];
        $lineNo = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNo++;
            if (count($row) !== count($headers)) continue;
            $data = array_combine($headers, array_map('trim', $row));
            if (empty(array_filter($data))) continue;

            $dob    = $this->normalizeDate($data['dob'] ?? '');
            $dobBs  = $data['dob_bs'] ?? '';
            $mobile = $data['mobile'] ?: ($data['guardian_contact'] ?? '');
            $error  = null;

            $exists = Student::where('organization', $org)
                ->where('stream', $stream)
                ->where('section', $section)
                ->where('roll_number', $data['roll_number'] ?? '')
                ->exists();

            if (empty($data['roll_number'])) {
                $error = 'Roll number is empty';
            } elseif (empty($data['first_name'])) {
                $error = 'First name is empty';
            } elseif (empty($data['last_name'])) {
                $error = 'Last name is empty';
            } elseif (empty($data['dob']) && empty($dobBs)) {
                $error = 'DOB is empty (provide dob or dob_bs)';
            } elseif (!empty($data['dob']) && $dob === null) {
                $error = "DOB '{$data['dob']}' is not a recognizable date";
            } elseif ($exists) {
                $error = "Roll #{$data['roll_number']} already exists in this group";
            }

            $rows[] = [
                'line'             => $lineNo,
                'roll_number'      => $data['roll_number']      ?? '',
                'first_name'       => $data['first_name']       ?? '',
                'middle_name'      => $data['middle_name']      ?? '',
                'last_name'        => $data['last_name']        ?? '',
                'gender'           => $data['gender']           ?? '',
                'dob'              => $dob ?? ($data['dob']     ?? ''),
                'dob_bs'           => $dobBs,
                'mobile'           => $mobile,
                'email'            => $data['email']            ?? '',
                'citizenship_no'   => $data['citizenship_no']   ?? '',
                'father_name'      => $data['father_name']      ?? '',
                'mother_name'      => $data['mother_name']      ?? '',
                'guardian_name'    => $data['guardian_name']    ?? '',
                'guardian_contact' => $data['guardian_contact'] ?? '',
                'address_en'       => $data['address_en']       ?? '',
                'registration_no'  => $data['registration_no']  ?? '',
                'designation'      => $data['designation']      ?? '',
                'employment_type'  => $data['employment_type']  ?? '',
                'program'          => $data['program']          ?? '',
                'batch'            => $data['batch']            ?? '',
                'error'            => $error,
            ];
        }
        fclose($handle);

        $context = compact('org', 'stream', 'section', 'type', 'validTill', 'createLearningAccounts', 'learningPasswordToken');
        session(['import_rows' => $rows, 'import_context' => $context]);

        return view('card.students.import', array_merge(compact('rows', 'context'), [
            'formOptions' => $this->buildImportOptions(),
        ]));
    }

    public function confirmCsv(Request $request)
    {
        $rows    = session('import_rows', []);
        $context = session('import_context');

        if (!$rows || !$context) {
            return redirect()->route('import.index')
                ->withErrors(['error' => 'Session expired. Please re-upload.']);
        }

        $this->assertScopedImport($context['org'], $context['stream'], $context['section']);

        $valid    = array_filter($rows, fn($r) => !$r['error']);
        $imported = 0;

        DB::transaction(function () use ($valid, $context, &$imported) {
            foreach ($valid as $row) {
                $dobAd = $row['dob'] ?: ($row['dob_bs'] ? $this->bsToAdDate($row['dob_bs']) : null);

                $student = Student::create([
                    'organization'     => $context['org'],
                    'member_type'      => $context['type'],
                    'stream'           => $context['stream'],
                    'section'          => $context['section'],
                    'valid_till'       => $context['validTill'],
                    'roll_number'      => $row['roll_number'],
                    'registration_no'  => $row['registration_no']  ?: null,
                    'first_name'       => $row['first_name'],
                    'middle_name'      => $row['middle_name']       ?: null,
                    'last_name'        => $row['last_name'],
                    'gender'           => $row['gender']            ?: null,
                    'dob'              => $dobAd,
                    'dob_bs'           => $row['dob_bs']            ?: null,
                    'mobile'           => $row['mobile']            ?: null,
                    'email'            => $row['email']             ?: null,
                    'citizenship_no'   => $row['citizenship_no']    ?: null,
                    'father_name'      => $row['father_name']       ?: null,
                    'mother_name'      => $row['mother_name']       ?: null,
                    'guardian_name'    => $row['guardian_name']     ?: null,
                    'guardian_contact' => $row['guardian_contact']  ?: null,
                    'address_en'       => $row['address_en']        ?: null,
                    'designation'      => $row['designation']       ?: null,
                    'employment_type'  => $row['employment_type']   ?: null,
                    'program'          => $row['program']           ?: null,
                    'batch'            => $row['batch']             ?: null,
                    'has_bus_pass'     => false,
                    'has_library_card' => false,
                ]);

                if (($context['createLearningAccounts'] ?? false) && in_array($context['type'], ['student', 'teacher'], true)) {
                    $password = isset($context['learningPasswordToken']) && $context['learningPasswordToken']
                        ? Crypt::decryptString($context['learningPasswordToken'])
                        : null;

                    app(MemberAccountService::class)->sync($student, $password);
                }

                $imported++;
            }
        });

        session()->forget(['import_rows', 'import_context']);

        return redirect()->route('students.index')
                         ->with('success', "{$imported} members imported successfully.");
    }

    // ═══════════════════════════════════════════════════════════════════════
    // EXCEL (XLSX/XLS) IMPORT — accepts IEMIS-style exports directly
    // ═══════════════════════════════════════════════════════════════════════

    public function previewXlsx(Request $request)
    {
        $request->validate([
            'xlsx_file'    => 'required|file|mimes:xlsx,xls,ods,csv,txt|max:10240',
            'organization' => 'required|string|max:100|exists:organizations,slug',
            'stream'       => 'nullable|string|max:100',
            'section'      => 'nullable|string|max:50',
            'valid_till'   => ['nullable', Rule::requiredIf($request->member_type === 'student'), 'date'],
            'member_type'  => 'required|in:student,teacher,staff',
        ]);

        $org       = $request->organization;
        $stream    = $request->stream ?: null;
        $section   = $request->section ?: null;
        $type      = $request->member_type;
        $validTill = $request->valid_till ?: null;

        $this->assertScopedImport($org, $stream, $section);

        try {
            $spreadsheet = IOFactory::load($request->file('xlsx_file')->getRealPath());
        } catch (\Throwable $e) {
            return back()->withErrors(['xlsx_file' => 'Could not read file: ' . $e->getMessage()]);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $data  = $sheet->toArray(null, true, true, false);

        if (empty($data)) {
            return back()->withErrors(['xlsx_file' => 'The file is empty.']);
        }

        // First non-empty row is the header
        $rawHeaders = array_shift($data);
        $headers = array_map(
            fn($h) => strtolower(trim((string) $h)),
            $rawHeaders
        );
        // Normalize: spaces/hyphens → underscore, strip parentheses
        $headers = array_map(
            fn($h) => preg_replace('/[^a-z0-9_]/', '_', str_replace([' ', '-'], '_', $h)),
            $headers
        );
        $headers = array_map(fn($h) => preg_replace('/_+/', '_', trim($h, '_')), $headers);

        // Map raw headers → internal field names using xlsxColumnMap
        $fieldMap = [];   // column index → internal field name (or null to skip)
        foreach ($headers as $i => $h) {
            $fieldMap[$i] = $this->xlsxColumnMap[$h] ?? null;
        }

        $hasRollCol = in_array('roll_number', $fieldMap);

        $rows   = [];
        $lineNo = 1;
        $autoRoll = 0;

        foreach ($data as $rawRow) {
            $lineNo++;
            // Skip fully empty rows
            if (empty(array_filter(array_map('strval', $rawRow)))) continue;

            // Build $data keyed by internal field name
            $row = [];
            foreach ($rawRow as $i => $val) {
                $field = $fieldMap[$i] ?? null;
                if (!$field) continue;

                $val = trim((string) $val);

                // Special: split FullName into first/middle/last
                if ($field === '_fullname') {
                    $parts = preg_split('/\s+/', $val, 3);
                    $row['first_name']  = ucfirst(strtolower($parts[0] ?? ''));
                    $row['last_name']   = ucfirst(strtolower($parts[count($parts) - 1] ?? ''));
                    $row['middle_name'] = count($parts) === 3
                        ? ucwords(strtolower($parts[1]))
                        : '';
                } else {
                    $row[$field] = $val;
                }
            }

            // Auto-assign roll number from row sequence if no column mapped
            if (!$hasRollCol || empty($row['roll_number'])) {
                $autoRoll++;
                $row['roll_number'] = (string) $autoRoll;
            }

            $dob    = $this->normalizeDate($row['dob'] ?? '');   // AD dob if present
            $dobBs  = $row['dob_bs'] ?? '';
            $mobile = ($row['mobile'] ?? '') ?: ($row['guardian_contact'] ?? '');

            $exists = Student::where('organization', $org)
                ->where('stream', $stream)
                ->where('section', $section)
                ->where('roll_number', $row['roll_number'])
                ->exists();

            $error = null;
            if (empty($row['first_name'])) {
                $error = 'First name is empty';
            } elseif (empty($row['last_name'])) {
                $error = 'Last name is empty';
            } elseif (empty($dob) && empty($dobBs)) {
                $error = 'DOB is empty';
            } elseif (!empty($row['dob']) && $dob === null) {
                $error = "DOB '{$row['dob']}' is not a recognizable date";
            } elseif ($exists) {
                $error = "Roll #{$row['roll_number']} already exists in this group";
            }

            $rows[] = [
                'line'             => $lineNo,
                'roll_number'      => $row['roll_number'],
                'first_name'       => $row['first_name']       ?? '',
                'middle_name'      => $row['middle_name']      ?? '',
                'last_name'        => $row['last_name']        ?? '',
                'gender'           => $row['gender']           ?? '',
                'dob'              => $dob ?? ($row['dob']     ?? ''),
                'dob_bs'           => $dobBs,
                'mobile'           => $mobile,
                'email'            => $row['email']            ?? '',
                'citizenship_no'   => $row['citizenship_no']   ?? '',
                'father_name'      => $row['father_name']      ?? '',
                'mother_name'      => $row['mother_name']      ?? '',
                'guardian_name'    => $row['guardian_name']    ?? '',
                'guardian_contact' => $row['guardian_contact'] ?? '',
                'address_en'       => $row['address_en']       ?? '',
                'registration_no'  => $row['registration_no']  ?? '',
                'designation'      => $row['designation']      ?? '',
                'employment_type'  => $row['employment_type']  ?? '',
                'program'          => $row['program']          ?? '',
                'batch'            => $row['batch']            ?? '',
                'error'            => $error,
            ];
        }

        if (empty($rows)) {
            return back()->withErrors(['xlsx_file' => 'No data rows found in the file.']);
        }

        $context = compact('org', 'stream', 'section', 'type', 'validTill');
        $context['createLearningAccounts'] = false;
        $context['learningPasswordToken']  = null;

        session(['import_rows' => $rows, 'import_context' => $context]);

        return view('card.students.import', array_merge(compact('rows', 'context'), [
            'formOptions' => $this->buildImportOptions(),
        ]));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PHOTO IMPORT — STEP 1: Preview
    // ═══════════════════════════════════════════════════════════════════════

    public function previewPhotos(Request $request)
    {
        $this->cleanupTempPhotoDirectory(session('photo_context.uuid'));

        $request->validate([
            'zip_file'     => 'required|file|mimes:zip|max:102400',
            'organization' => 'required|string|max:100|exists:organizations,slug',
            'stream'       => 'nullable|string|max:100',
            'section'      => 'nullable|string|max:50',
        ]);

        $org     = $request->organization;
        $stream  = $request->stream ?: null;
        $section = $request->section ?: null;

        $this->assertScopedImport($org, $stream, $section);

        // Load students in this group indexed by roll_number
        $students = Student::where('organization', $org)
            ->where('stream', $stream)
            ->where('section', $section)
            ->get()
            ->keyBy('roll_number');

        if ($students->isEmpty()) {
            return back()->withErrors(['zip_file' => 'No students found for the selected Organization / Stream / Section.']);
        }

        // Extract ZIP to a temp directory under public so images are serveable
        $uuid    = Str::uuid()->toString();
        $tempDir = public_path("temp_photos/{$uuid}");
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);

        $zip     = new ZipArchive();
        $zipPath = $request->file('zip_file')->getRealPath();

        if ($zip->open($zipPath) !== true) {
            return back()->withErrors(['zip_file' => 'Could not open ZIP file.']);
        }

        $photoRows = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with($name, '/') || str_starts_with(basename($name), '.')) continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $this->imageExts)) continue;

            $roll = pathinfo(basename($name), PATHINFO_FILENAME);

            // Save temp file
            $tempFile = "{$tempDir}/{$roll}.{$ext}";
            file_put_contents($tempFile, $zip->getFromIndex($i));

            $student = $students->get($roll);

            $photoRows[] = [
                'roll'         => $roll,
                'filename'     => basename($name),
                'ext'          => $ext,
                'temp_url'     => asset("temp_photos/{$uuid}/{$roll}.{$ext}"),
                'student_id'   => $student?->id,
                'student_name' => $student ? $student->full_name : null,
                'current_photo'=> $student?->photo_url,
                'has_photo'    => $student && !empty($student->photo),
                // action: 'add' | 'replace' | 'no_match'
                'action'       => $student ? ($student->photo ? 'replace' : 'add') : 'no_match',
            ];
        }

        $zip->close();

        // Sort: add first, replace second, no_match last
        usort($photoRows, fn($a, $b) => strcmp(
            ['add' => 0, 'replace' => 1, 'no_match' => 2][$a['action']],
            ['add' => 0, 'replace' => 1, 'no_match' => 2][$b['action']]
        ));

        $photoContext = compact('org', 'stream', 'section', 'uuid');
        session(['photo_rows' => $photoRows, 'photo_context' => $photoContext]);

        return view('card.students.import', [
            'photoRows'    => $photoRows,
            'photoContext' => $photoContext,
            'formOptions'  => $this->buildImportOptions(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PHOTO IMPORT — STEP 2: Confirm
    // ═══════════════════════════════════════════════════════════════════════

    public function confirmPhotos(Request $request)
    {
        $photoRows    = session('photo_rows', []);
        $photoContext = session('photo_context');

        if (!$photoRows || !$photoContext) {
            return redirect()->route('import.index')
                ->withErrors(['error' => 'Session expired. Please re-upload the ZIP.']);
        }

        $this->assertScopedImport($photoContext['org'], $photoContext['stream'], $photoContext['section']);

        $uuid    = $photoContext['uuid'];
        $tempDir = public_path("temp_photos/{$uuid}");
        $destDir = public_path('photos');
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);

        // Which rolls the user wants to skip (no_match rows are always skipped)
        $skip = array_flip($request->input('skip', []));

        $added    = 0;
        $replaced = 0;

        foreach ($photoRows as $row) {
            if ($row['action'] === 'no_match') continue;
            if (isset($skip[$row['roll']])) continue;

            $tempFile = "{$tempDir}/{$row['roll']}.{$row['ext']}";
            if (!file_exists($tempFile)) continue;

            $destFile = "{$destDir}/{$row['roll']}.{$row['ext']}";
            copy($tempFile, $destFile);

            Student::where('id', $row['student_id'])
                   ->update(['photo' => "photos/{$row['roll']}.{$row['ext']}"]);

            $row['action'] === 'replace' ? $replaced++ : $added++;
        }

        $this->cleanupTempPhotoDirectory($uuid);

        session()->forget(['photo_rows', 'photo_context']);

        return redirect()->route('students.index')
            ->with('success', "{$added} photo(s) added, {$replaced} replaced.");
    }

    // ── CSV template download ─────────────────────────────────────────────
    public function downloadTemplate()
    {
        $headers = [
            'roll_number', 'first_name', 'middle_name', 'last_name',
            'gender', 'dob', 'dob_bs', 'mobile',
            'father_name', 'mother_name', 'guardian_name', 'guardian_contact',
            'email', 'citizenship_no', 'registration_no', 'address_en',
            'designation', 'employment_type', 'program', 'batch',
        ];

        $content  = implode(',', $headers) . "\n";
        $content .= "ST-001,Ram,Kumar,Sharma,Male,2005-01-15,2062-09-30,9800000001,";
        $content .= "Father Name,Mother Name,Guardian Name,9800000002,";
        $content .= "ram@email.com,123-456,,Badikedar-2 Doti,,,Science,2080\n";

        return response($content, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_template.csv"',
        ]);
    }

    // ── Convert BS (Bikram Sambat) date string to approximate AD date ─────
    // Accuracy: ±15 days. Month and year are correct for students born after BS 2000.
    private function bsToAdDate(string $bsDate): ?string
    {
        if (!preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', trim($bsDate), $m)) {
            return null;
        }

        $bsYear  = (int) $m[1];
        $bsMonth = (int) $m[2];
        $bsDay   = (int) $m[3];

        // BS months 1–9 (Baisakh–Ashwin): AD year = BS year − 57, AD month = BS month + 3
        // BS months 10–12 (Kartik–Chaitra): AD year = BS year − 56, AD month = BS month − 9
        if ($bsMonth <= 9) {
            $adYear  = $bsYear - 57;
            $adMonth = $bsMonth + 3;
        } else {
            $adYear  = $bsYear - 56;
            $adMonth = $bsMonth - 9;
        }

        try {
            $maxDay = (int) Carbon::createFromDate($adYear, $adMonth, 1)->endOfMonth()->day;
            $adDay  = min($bsDay, $maxDay);
            return sprintf('%04d-%02d-%02d', $adYear, $adMonth, $adDay);
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Normalize any common date format → Y-m-d ─────────────────────────
    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;

        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d', 'd M Y', 'd F Y'];

        foreach ($formats as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $value)->format('Y-m-d');
            } catch (\Throwable) {}
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function assertScopedImport(string $organization, ?string $stream, ?string $section): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($user->organizationSlug()) {
            abort_unless($organization === $user->organizationSlug(), 403);
        }

        if ($user->departmentName()) {
            abort_unless($stream === $user->departmentName(), 403);
        }
    }

    private function buildImportOptions(): array
    {
        $user = auth()->user();

        if (Schema::hasTable('organizations') && Schema::hasTable('departments') && Schema::hasTable('sections')) {
            $organizations = Organization::where('is_active', true)
                ->when(
                    !$user->isSuperAdmin() && $user->organizationSlug(),
                    fn($query) => $query->where('slug', $user->organizationSlug())
                )
                ->with(['activeDepartments.activeSections'])
                ->orderBy('name')
                ->get();

            return $organizations->mapWithKeys(fn($organization) => [
                $organization->slug => [
                    'label' => $organization->name,
                    'streams' => $organization->activeDepartments
                        ->when(!$user->isSuperAdmin() && $user->departmentName(), fn($departments) => $departments->where('name', $user->departmentName()))
                        ->mapWithKeys(fn($department) => [
                            $department->name => $department->activeSections->pluck('name')->values()->all(),
                        ])
                        ->all(),
                ],
            ])->all();
        }

        return Student::query()
            ->select('organization', 'stream', 'section')
            ->whereNotNull('organization')
            ->where('organization', '!=', '')
            ->whereNotNull('stream')
            ->where('stream', '!=', '')
            ->orderBy('organization')
            ->orderBy('stream')
            ->orderBy('section')
            ->tap(fn($query) => $user->applyStudentScope($query))
            ->get()
            ->groupBy('organization')
            ->map(function (Collection $organizationStudents, string $organization) {
                return [
                    'label' => ucfirst($organization),
                    'streams' => $organizationStudents
                        ->groupBy('stream')
                        ->map(fn(Collection $streamStudents) => $streamStudents
                            ->pluck('section')
                            ->filter()
                            ->unique()
                            ->values()
                            ->all())
                        ->all(),
                ];
            })
            ->all();
    }
}
