<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $query = Complaint::with('student')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('member_type'), fn ($q) => $q->whereHas('student', fn ($s) => $s->where('member_type', $request->member_type)))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where(fn ($inner) => $inner->where('subject', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($s) => $s->where(fn ($name) => $name
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%"))));
            });

        $complaints = $query->latest()->paginate(20)->withQueryString();
        $counts = [
            'all' => Complaint::count(),
            'pending' => Complaint::where('status', 'pending')->count(),
            'in_review' => Complaint::where('status', 'in_review')->count(),
            'resolved' => Complaint::where('status', 'resolved')->count(),
            'rejected' => Complaint::where('status', 'rejected')->count(),
        ];

        return view('hr.complaints.index', compact('complaints', 'counts'));
    }

    public function show(Complaint $complaint)
    {
        $complaint->load(['student', 'respondedBy']);

        return view('hr.complaints.show', compact('complaint'));
    }

    public function respond(Request $request, Complaint $complaint)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['in_review', 'resolved', 'rejected'])],
            'admin_response' => ['required', 'string', 'max:3000'],
        ]);

        $complaint->update([
            'status' => $data['status'],
            'admin_response' => $data['admin_response'],
            'responded_by' => auth()->id(),
            'responded_at' => now(),
        ]);

        return redirect()->route('admin.hr.complaints.show', $complaint)->with('success', 'Response sent to '.$complaint->student->full_name.'.');
    }
}
