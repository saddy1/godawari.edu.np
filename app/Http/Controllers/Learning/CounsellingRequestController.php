<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Counselling\CounsellingSession;
use Illuminate\Http\Request;

class CounsellingRequestController extends Controller
{
    // Students only ever see their own status/schedule here — never the
    // `report` column, which is private to the counsellor and super-admin.
    public function index(Request $request)
    {
        $student = $request->user()->student;
        abort_unless($student, 403, 'No student profile is linked to this account.');

        $sessions = CounsellingSession::with('counsellor:id,name')
            ->where('student_id', $student->id)
            ->latest()
            ->get(['id', 'counsellor_id', 'topic', 'status', 'scheduled_at', 'created_at']);

        return view('learning.counselling.index', compact('sessions'));
    }

    public function store(Request $request)
    {
        $student = $request->user()->student;
        abort_unless($student, 403, 'No student profile is linked to this account.');

        $data = $request->validate(['topic' => ['nullable', 'string', 'max:255']]);

        CounsellingSession::create([
            'student_id' => $student->id,
            'requested_by' => $request->user()->id,
            'topic' => $data['topic'] ?? null,
            'status' => 'requested',
        ]);

        return back()->with('success', 'Your counselling request has been sent. You will be notified once a counsellor is assigned.');
    }
}
