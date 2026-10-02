<?php

namespace App\Http\Controllers\Counselling;

use App\Http\Controllers\Controller;
use App\Models\Card\Student;
use App\Models\Counselling\CounsellingSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CounsellingController extends Controller
{
    // The module's landing page — stats at a glance, recent activity, and (for
    // admins) a per-counsellor breakdown. Booking a new session also lives
    // here so it's reachable in one click rather than buried in the list page.
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $canManage = $user->canAccess('counselling.manage');
        abort_unless($canManage || $user->isCounsellor(), 403);

        $base = fn () => CounsellingSession::query()->when(! $canManage, fn ($q) => $q->where('counsellor_id', $user->id));

        $stats = [
            'requested' => $base()->where('status', 'requested')->count(),
            'scheduled' => $base()->where('status', 'scheduled')->count(),
            'completed' => $base()->where('status', 'completed')->count(),
            'cancelled' => $base()->where('status', 'cancelled')->count(),
        ];

        $recentSessions = $base()->with(['student', 'counsellor'])->latest()->limit(8)->get();

        // Only an admin sees across the whole caseload — a lone counsellor's
        // "breakdown" would just be their own numbers, already in $stats.
        $counsellorBreakdown = $canManage
            ? User::role('counsellor')->orderBy('name')->get(['id', 'name'])->map(fn (User $counsellor) => (object) [
                'counsellor' => $counsellor,
                'requested' => CounsellingSession::where('counsellor_id', $counsellor->id)->where('status', 'requested')->count(),
                'scheduled' => CounsellingSession::where('counsellor_id', $counsellor->id)->where('status', 'scheduled')->count(),
                'completed' => CounsellingSession::where('counsellor_id', $counsellor->id)->where('status', 'completed')->count(),
            ])
            : collect();

        $students = $canManage ? Student::where('member_type', 'student')->orderBy('first_name')->limit(500)->get(['id', 'first_name', 'middle_name', 'last_name', 'roll_number']) : collect();
        $counsellors = $canManage ? User::role('counsellor')->orderBy('name')->get(['id', 'name']) : collect();

        return view('counselling.dashboard', compact('stats', 'recentSessions', 'counsellorBreakdown', 'canManage', 'students', 'counsellors'));
    }

    public function sessions(Request $request)
    {
        $user = $request->user();
        $canManage = $user->canAccess('counselling.manage');
        abort_unless($canManage || $user->isCounsellor(), 403);

        $status = in_array($request->query('status'), ['requested', 'scheduled', 'completed', 'cancelled'], true)
            ? $request->query('status') : null;

        $query = CounsellingSession::with(['student', 'counsellor', 'requestedBy'])->latest();
        // An admin sees every session to book/assign; a counsellor who isn't
        // also an admin only ever sees the sessions assigned to them — they
        // have no business browsing anyone else's caseload.
        if (! $canManage) {
            $query->where('counsellor_id', $user->id);
        }
        $sessions = $query->when($status, fn ($q) => $q->where('status', $status))->paginate(25)->withQueryString();
        $counsellors = $canManage ? User::role('counsellor')->orderBy('name')->get(['id', 'name']) : collect();

        return view('counselling.sessions', compact('sessions', 'status', 'canManage', 'counsellors'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->canAccess('counselling.manage'), 403);

        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'counsellor_id' => ['required', 'integer', 'exists:users,id'],
            'scheduled_at' => ['required', 'date'],
            'topic' => ['nullable', 'string', 'max:255'],
        ]);

        CounsellingSession::create([
            ...$data,
            'status' => 'scheduled',
            'created_by' => $user->id,
        ]);

        return back()->with('success', 'Counselling session booked.');
    }

    public function assign(Request $request, CounsellingSession $counsellingSession)
    {
        abort_unless($request->user()->canAccess('counselling.manage'), 403);
        abort_if($counsellingSession->status !== 'requested', 422, 'Only a requested session can be assigned.');

        $data = $request->validate([
            'counsellor_id' => ['required', 'integer', 'exists:users,id'],
            'scheduled_at' => ['required', 'date'],
        ]);

        $counsellingSession->update([...$data, 'status' => 'scheduled']);

        return back()->with('success', 'Counsellor assigned and session scheduled.');
    }

    public function cancel(Request $request, CounsellingSession $counsellingSession)
    {
        abort_unless($request->user()->canAccess('counselling.manage'), 403);
        $counsellingSession->update(['status' => 'cancelled']);

        return back()->with('success', 'Session cancelled.');
    }

    public function show(Request $request, CounsellingSession $counsellingSession)
    {
        $user = $request->user();
        $canManage = $user->canAccess('counselling.manage');
        abort_unless($canManage || (int) $counsellingSession->counsellor_id === (int) $user->id, 403);

        $counsellingSession->load(['student', 'counsellor', 'requestedBy']);
        $canViewReport = $counsellingSession->canViewReport($user);

        return view('counselling.show', compact('counsellingSession', 'canManage', 'canViewReport'));
    }

    // Writing the report is restricted per-record (super-admin or specifically
    // the assigned counsellor) — never by a blanket permission, since the
    // whole point is that no one else, including other counsellors or
    // whoever has counselling.manage, can read or write it.
    public function saveReport(Request $request, CounsellingSession $counsellingSession)
    {
        abort_unless($counsellingSession->canViewReport($request->user()), 403);

        $data = $request->validate(['report' => ['required', 'string', 'max:5000']]);

        $counsellingSession->update([
            'report' => $data['report'],
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Counselling report saved.');
    }
}
