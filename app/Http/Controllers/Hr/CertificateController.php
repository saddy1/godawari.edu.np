<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Card\Student;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $certificates = Certificate::with('member')
            ->when($request->filled('type'), fn ($q) => $q->where('certificate_type', $request->type))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($inner) use ($search) {
                    $inner->where('certificate_number', 'like', "%{$search}%")
                        ->orWhere('student_name', 'like', "%{$search}%")
                        ->orWhere('symbol_no', 'like', "%{$search}%")
                        ->orWhere('registration_no', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all' => Certificate::count(),
            'character' => Certificate::where('certificate_type', 'character')->count(),
            'provisional' => Certificate::where('certificate_type', 'provisional')->count(),
        ];

        return view('card.certificates.index', compact('certificates', 'counts'));
    }

    public function create(Request $request)
    {
        $member = null;
        $memberId = $request->input('member_id') ?: $request->old('member_id');
        if ($memberId) {
            $member = Student::select([
                'id', 'first_name', 'middle_name', 'last_name', 'roll_number',
                'stream', 'section', 'gender', 'father_name', 'mother_name',
                'guardian_name', 'registration_no', 'photo',
                'permanent_municipality', 'permanent_ward',
                'permanent_district', 'permanent_province',
            ])->find($memberId);
        }

        return view('card.certificates.create', compact('member'));
    }

    public function searchStudents(Request $request)
    {
        $q       = trim((string) $request->input('q', ''));
        $stream  = trim((string) $request->input('stream', ''));
        $section = trim((string) $request->input('section', ''));

        $students = Student::where('member_type', 'student')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('first_name', 'like', "%{$q}%")
                          ->orWhere('middle_name', 'like', "%{$q}%")
                          ->orWhere('last_name', 'like', "%{$q}%")
                          ->orWhere('roll_number', 'like', "%{$q}%")
                          ->orWhere('registration_no', 'like', "%{$q}%")
                          ->orWhere('father_name', 'like', "%{$q}%")
                          ->orWhere('guardian_name', 'like', "%{$q}%")
                          ->orWhereRaw("TRIM(CONCAT_WS(' ', first_name, middle_name, last_name)) LIKE ?", ["%{$q}%"]);
                });
            })
            ->when($stream !== '', fn ($query) => $query->where('stream', $stream))
            ->when($section !== '', fn ($query) => $query->where('section', $section))
            ->orderBy('first_name')->orderBy('last_name')
            ->limit(20)
            ->get([
                'id', 'first_name', 'middle_name', 'last_name', 'roll_number',
                'stream', 'section', 'gender', 'photo',
                'father_name', 'mother_name', 'guardian_name', 'registration_no',
                'permanent_municipality', 'permanent_ward',
                'permanent_district', 'permanent_province',
            ])
            ->map(function (Student $s) {
                $addrParts = array_filter([
                    $s->permanent_municipality,
                    $s->permanent_ward ? 'Ward-' . $s->permanent_ward : null,
                    $s->permanent_district,
                ]);
                return [
                    'id'              => $s->id,
                    'name'            => trim("{$s->first_name} {$s->middle_name} {$s->last_name}"),
                    'roll_number'     => $s->roll_number,
                    'stream'          => $s->stream,
                    'section'         => $s->section,
                    'gender'          => $s->gender,
                    'photo_url'       => $s->photo_url,
                    'father_name'     => $s->father_name,
                    'mother_name'     => $s->mother_name,
                    'guardian_name'   => $s->guardian_name,
                    'registration_no' => $s->registration_no,
                    'address'         => implode(', ', $addrParts),
                ];
            })
            ->values();

        return response()->json(['results' => $students]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'member_id'             => ['required', 'exists:students,id'],
            'certificate_type'      => ['required', Rule::in(['character', 'provisional'])],
            'exam_name'             => ['required', 'string', 'max:150'],
            'division_gpa'          => ['nullable', 'string', 'max:100'],
            'pass_year_bs'          => ['nullable', 'string', 'max:30'],
            'pass_year_ad'          => ['nullable', 'string', 'max:10'],
            'character_description' => ['nullable', 'string', 'max:100'],
            'symbol_no'             => ['nullable', 'string', 'max:100'],
            'issue_date'            => ['required', 'date'],
            'student_name'          => ['required', 'string', 'max:200'],
            'parent_name'           => ['nullable', 'string', 'max:200'],
            'address'               => ['nullable', 'string', 'max:300'],
            'registration_no'       => ['nullable', 'string', 'max:100'],
            'gender'                => ['nullable', 'string', 'max:30'],
        ]);

        $data['certificate_number'] = Certificate::generateNumber();
        $data['issued_by'] = auth()->id();

        $certificate = Certificate::create($data);

        return redirect()->route('certificates.print', $certificate)
            ->with('success', "Certificate #{$certificate->certificate_number} generated.");
    }

    public function show(Certificate $certificate)
    {
        return redirect()->route('certificates.print', $certificate);
    }

    public function print(Certificate $certificate)
    {
        $certificate->load('member');
        return view('card.certificates.print', compact('certificate'));
    }

    public function destroy(Certificate $certificate)
    {
        $certificate->delete();
        return back()->with('success', 'Certificate record deleted.');
    }
}
