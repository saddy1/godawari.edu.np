<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Controller;
use App\Models\Work\WorkChecklist;
use Illuminate\Http\Request;

class WorkChecklistController extends Controller
{
    public function index()
    {
        $checklists = WorkChecklist::with('items')->latest()->paginate(12);

        return view('work-tasks.checklists.index', compact('checklists'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'default_max_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'default_incentive_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'items' => ['required', 'string'],
        ]);

        $items = collect(preg_split('/\r\n|\r|\n/', $data['items']))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->values();

        if ($items->isEmpty()) {
            return back()->withErrors(['items' => 'Add at least one checklist item.']);
        }

        $checklist = WorkChecklist::create([
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'description' => $data['description'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        foreach ($items as $index => $title) {
            $checklist->items()->create([
                'title' => $title,
                'category' => $data['category'] ?? null,
                'max_score' => $data['default_max_score'],
                'incentive_amount' => $data['default_incentive_amount'] ?? null,
                'sort_order' => $index + 1,
            ]);
        }

        return back()->with('success', 'Checklist template created.');
    }

    public function destroy(WorkChecklist $checklist)
    {
        $checklist->delete();

        return back()->with('success', 'Checklist template deleted.');
    }
}
