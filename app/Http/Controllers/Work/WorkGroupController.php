<?php

namespace App\Http\Controllers\Work;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Work\WorkGroup;
use Illuminate\Http\Request;

class WorkGroupController extends Controller
{
    public function index()
    {
        $groups = WorkGroup::with('members')->latest()->paginate(12);
        $teachers = User::role('teacher')->where('is_active', true)->orderBy('name')->get();

        return view('work-tasks.groups.index', compact('groups', 'teachers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $group = WorkGroup::create([
            'name' => $data['name'],
            'type' => $data['type'] ?? null,
            'description' => $data['description'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $group->members()->sync($data['member_ids'] ?? []);

        return back()->with('success', 'Work group created.');
    }

    public function update(Request $request, WorkGroup $group)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $group->update([
            'name' => $data['name'],
            'type' => $data['type'] ?? null,
            'description' => $data['description'] ?? null,
        ]);
        $group->members()->sync($data['member_ids'] ?? []);

        return back()->with('success', 'Work group updated.');
    }

    public function destroy(WorkGroup $group)
    {
        if ($group->tasks()->exists()) {
            return back()->withErrors([
                'group' => 'This group has assigned tasks and cannot be deleted.',
            ]);
        }

        $group->delete();

        return back()->with('success', 'Work group deleted.');
    }
}
