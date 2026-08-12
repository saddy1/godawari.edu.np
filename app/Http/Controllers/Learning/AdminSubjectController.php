<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Learning\LearningClass;
use App\Models\Learning\LearningSubject;
use Illuminate\Http\Request;

class AdminSubjectController extends Controller
{
    public function index()
    {
        $this->denyScopedTeacher();
        $classes = LearningClass::with([
                'subjects' => fn ($query) => $query->withCount('courses')->orderBy('name'),
            ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('learning.admin.subjects.index', compact('classes'));
    }

    public function store(Request $request)
    {
        $this->denyScopedTeacher();
        $data = $request->validate([
            'learning_class_id' => ['required', 'exists:learning_classes,id'],
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        LearningSubject::create($data);

        return back()->with('success', 'Subject added.');
    }

    public function update(Request $request, LearningSubject $subject)
    {
        $this->denyScopedTeacher();
        $data = $request->validate([
            'learning_class_id' => ['required', 'exists:learning_classes,id'],
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $subject->update($data);

        return back()->with('success', 'Subject updated.');
    }

    public function destroy(LearningSubject $subject)
    {
        $this->denyScopedTeacher();
        $subject->delete();

        return back()->with('success', 'Subject deleted.');
    }

    private function denyScopedTeacher(): void
    {
        $user = auth()->user();
        abort_if($user?->isTeacher() && ! $user->isSuperAdmin() && ! $user->isPrincipal() && ! $user->hasRole('administrator'), 403);
    }
}
