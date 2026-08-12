<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\FacultyGroup;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class FacultyController extends Controller
{
    public function index()
    {
        $groups = FacultyGroup::withCount('members')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $ungroupedCount = Faculty::whereNull('faculty_group_id')->count();

        return view('backend.faculty.index', compact('groups', 'ungroupedCount'));
    }

    public function create()
    {
        $groups = FacultyGroup::orderBy('sort_order')->orderBy('name')->get();
        $selectedGroupId = request('group');
        $selectedGroup = $selectedGroupId
            ? FacultyGroup::findOrFail($selectedGroupId)
            : null;

        return view('backend.faculty.create', compact('groups', 'selectedGroupId', 'selectedGroup'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'faculty_group_id' => 'required|exists:faculty_groups,id',
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'education' => 'required|string',
            'image_media_path' => 'nullable|string|max:500',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->except(['image_file', 'image_media_path']);
        $this->syncCategoryFromGroup($data);
        $data['is_active'] = $request->boolean('is_active', true);

        if ($mediaPath = $this->selectedMediaPath($request)) {
            $data['image'] = $mediaPath;
        } elseif ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $filename = time() . '_' . \Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('faculty'), $filename);
            $data['image'] = 'faculty/' . $filename;
        }

        $faculty = Faculty::create($data);

        return redirect()->route('admin.faculty.groups.show', $faculty->faculty_group_id)->with('success', 'Faculty member added successfully!');
    }

    public function edit(Faculty $faculty)
    {
        $groups = FacultyGroup::orderBy('sort_order')->orderBy('name')->get();

        return view('backend.faculty.edit', compact('faculty', 'groups'));
    }

    public function update(Request $request, Faculty $faculty)
    {
        $request->validate([
            'faculty_group_id' => 'required|exists:faculty_groups,id',
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'education' => 'required|string',
            'image_media_path' => 'nullable|string|max:500',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->except(['image_file', 'image_media_path']);
        $this->syncCategoryFromGroup($data);
        $data['is_active'] = $request->boolean('is_active');

        if ($mediaPath = $this->selectedMediaPath($request)) {
            $this->deleteImage($faculty->image);
            $data['image'] = $mediaPath;
        } elseif ($request->hasFile('image_file')) {
            // Delete old image
            $this->deleteImage($faculty->image);

            $file = $request->file('image_file');
            $filename = time() . '_' . \Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('faculty'), $filename);
            $data['image'] = 'faculty/' . $filename;
        }

        $faculty->update($data);

        return redirect()->route('admin.faculty.groups.show', $faculty->faculty_group_id)->with('success', 'Faculty profile updated!');
    }

    public function destroy(Faculty $faculty)
    {
        $groupId = $faculty->faculty_group_id;

        $this->deleteImage($faculty->image);

        $faculty->delete();

        return $groupId
            ? redirect()->route('admin.faculty.groups.show', $groupId)->with('success', 'Faculty member removed.')
            : redirect()->route('admin.faculty.index')->with('success', 'Faculty member removed.');
    }

    public function storeGroup(Request $request)
    {
        FacultyGroup::create($request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]) + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('admin.faculty.index')->with('success', 'Faculty group created successfully!');
    }

    public function editGroup(FacultyGroup $group)
    {
        return view('backend.faculty.group-edit', compact('group'));
    }

    public function showGroup(FacultyGroup $group)
    {
        $group->load(['members' => fn ($query) => $query->orderBy('order')->orderBy('name')]);
        $ungroupedFaculties = Faculty::whereNull('faculty_group_id')
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        return view('backend.faculty.group-members', compact('group', 'ungroupedFaculties'));
    }

    public function updateGroup(Request $request, FacultyGroup $group)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $group->update($data);
        Faculty::where('faculty_group_id', $group->id)->update(['category' => $group->name]);

        return redirect()->route('admin.faculty.index')->with('success', 'Faculty group updated successfully!');
    }

    public function destroyGroup(FacultyGroup $group)
    {
        if ($group->members()->exists()) {
            return redirect()->route('admin.faculty.index')->with('error', 'Move or delete members before deleting this group.');
        }

        $group->delete();

        return redirect()->route('admin.faculty.index')->with('success', 'Faculty group deleted successfully!');
    }

    private function syncCategoryFromGroup(array &$data): void
    {
        if (! empty($data['faculty_group_id'])) {
            $data['category'] = FacultyGroup::whereKey($data['faculty_group_id'])->value('name');
        }

        $data['category'] = $data['category'] ?? 'General';
    }

    private function selectedMediaPath(Request $request): ?string
    {
        $path = $request->input('image_media_path');

        if (! $path) {
            return null;
        }

        return Media::where('file_path', $path)
            ->where('mime_type', 'like', 'image/%')
            ->value('file_path');
    }

    private function deleteImage(?string $path): void
    {
        if (! $path || str_starts_with($path, 'media/')) {
            return;
        }

        $fullPath = public_path($path);
        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }
}
