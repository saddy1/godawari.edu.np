<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Card\Department;
use App\Models\Learning\LearningClass;
use App\Services\LearningClassSyncService;
use Illuminate\Http\Request;

class AdminClassController extends Controller
{
    public function index()
    {
        $this->denyScopedTeacher();
        app(LearningClassSyncService::class)->syncFromCardDepartments();

        $classes = LearningClass::withCount(['subjects', 'courses'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $cardClasses = Department::query()
            ->with(['organization', 'sections' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('learning.admin.classes.index', compact('classes', 'cardClasses'));
    }

    public function store(Request $request)
    {
        $this->denyScopedTeacher();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        LearningClass::create($data);

        return back()->with('success', 'Class added.');
    }

    public function update(Request $request, LearningClass $class)
    {
        $this->denyScopedTeacher();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $class->update($data);

        return back()->with('success', 'Class updated.');
    }

    public function destroy(LearningClass $class)
    {
        $this->denyScopedTeacher();
        $class->delete();

        return back()->with('success', 'Class deleted.');
    }

    private function denyScopedTeacher(): void
    {
        $user = auth()->user();
        abort_if($user?->isTeacher() && ! $user->isSuperAdmin() && ! $user->isPrincipal() && ! $user->hasRole('administrator'), 403);
    }

}
