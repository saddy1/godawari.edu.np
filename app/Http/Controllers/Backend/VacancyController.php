<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Vacancy;
use App\Models\VacancyApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VacancyController extends Controller
{
    public function index()
    {
        $vacancies = Vacancy::withCount('applications')->latest()->paginate(15);
        return view('backend.vacancies.index', compact('vacancies'));
    }

    public function create()
    {
        return view('backend.vacancies.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'required|string',
            'requirements'   => 'nullable|string',
            'department'     => 'nullable|string|max:100',
            'type'           => 'required|string|in:Full Time,Part Time,Contract',
            'deadline'       => 'nullable|date',
            'document'       => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'featured_image_media_path' => 'nullable|string|max:500',
            'featured_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:4096',
            'is_active'      => 'nullable|boolean',
        ]);

        Vacancy::create([
            'title'          => $request->title,
            'description'    => $request->description,
            'requirements'   => $request->requirements,
            'department'     => $request->department,
            'type'           => $request->type,
            'deadline'       => $request->deadline,
            'document_path'  => $request->hasFile('document')
                                    ? $this->saveFile($request->file('document'), 'vacancy-documents')
                                    : null,
            'featured_image' => $this->selectedMediaPath($request)
                                    ?: ($request->hasFile('featured_image')
                                    ? $this->saveFile($request->file('featured_image'), 'vacancy-images')
                                    : null),
            'is_active'      => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.vacancies.index')->with('success', 'Vacancy posted successfully.');
    }

    public function edit(Vacancy $vacancy)
    {
        return view('backend.vacancies.edit', compact('vacancy'));
    }

    public function update(Request $request, Vacancy $vacancy)
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'required|string',
            'requirements'   => 'nullable|string',
            'department'     => 'nullable|string|max:100',
            'type'           => 'required|string|in:Full Time,Part Time,Contract',
            'deadline'       => 'nullable|date',
            'document'       => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'featured_image_media_path' => 'nullable|string|max:500',
            'featured_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:4096',
            'is_active'      => 'nullable|boolean',
        ]);

        $data = [
            'title'        => $request->title,
            'description'  => $request->description,
            'requirements' => $request->requirements,
            'department'   => $request->department,
            'type'         => $request->type,
            'deadline'     => $request->deadline,
            'is_active'    => $request->boolean('is_active', true),
        ];

        if ($request->hasFile('document')) {
            $this->deleteFile($vacancy->document_path);
            $data['document_path'] = $this->saveFile($request->file('document'), 'vacancy-documents');
        }

        if ($mediaPath = $this->selectedMediaPath($request)) {
            $this->deleteFile($vacancy->featured_image);
            $data['featured_image'] = $mediaPath;
        } elseif ($request->hasFile('featured_image')) {
            $this->deleteFile($vacancy->featured_image);
            $data['featured_image'] = $this->saveFile($request->file('featured_image'), 'vacancy-images');
        }

        if ($request->boolean('remove_image') && $vacancy->featured_image) {
            $this->deleteFile($vacancy->featured_image);
            $data['featured_image'] = null;
        }

        $vacancy->update($data);

        return redirect()->route('admin.vacancies.index')->with('success', 'Vacancy updated successfully.');
    }

    public function destroy(Vacancy $vacancy)
    {
        DB::transaction(function () use ($vacancy) {
            $vacancy->load('applications');

            foreach ($vacancy->applications as $application) {
                $this->deleteApplicationFiles($application);
                $application->delete();
            }

            $this->deleteFile($vacancy->document_path);
            $this->deleteFile($vacancy->featured_image);
            $vacancy->delete();
        });

        return redirect()->route('admin.vacancies.index')->with('success', 'Vacancy deleted successfully.');
    }

    public function toggle(Vacancy $vacancy)
    {
        $vacancy->update(['is_active' => !$vacancy->is_active]);
        return back()->with('success', 'Vacancy status updated.');
    }

    public function applications(Vacancy $vacancy)
    {
        $applications = $vacancy->applications()->latest()->paginate(20);
        return view('backend.vacancy-applications.index', compact('vacancy', 'applications'));
    }

    public function showApplication(VacancyApplication $application)
    {
        $application->load('vacancy');
        return view('backend.vacancy-applications.show', compact('application'));
    }

    public function updateApplication(Request $request, VacancyApplication $application)
    {
        $request->validate([
            'status'        => 'required|in:Pending,Reviewed,Shortlisted,Rejected',
            'admin_remarks' => 'nullable|string',
        ]);

        $application->update([
            'status'        => $request->status,
            'admin_remarks' => $request->admin_remarks,
        ]);

        return back()->with('success', 'Application status updated.');
    }

    public function destroyApplication(VacancyApplication $application)
    {
        $this->deleteApplicationFiles($application);
        $application->delete();
        return redirect()->route('admin.vacancies.index')->with('success', 'Application deleted.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function saveFile($file, string $folder): string
    {
        $dir = public_path("uploads/{$folder}");
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $filename);
        return "uploads/{$folder}/{$filename}";
    }

    private function deleteFile(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'media/')) {
            $full = public_path($path);
            if (file_exists($full)) {
                @unlink($full);
            }
        }
    }

    private function selectedMediaPath(Request $request): ?string
    {
        $path = $request->input('featured_image_media_path');

        if (! $path) {
            return null;
        }

        return Media::where('file_path', $path)
            ->where('mime_type', 'like', 'image/%')
            ->value('file_path');
    }

    private function deleteApplicationFiles(VacancyApplication $application): void
    {
        $this->deleteFile($application->cv_path);
        $this->deleteFile($application->profile_photo);
        $this->deleteFile($application->citizen_front_path);
        $this->deleteFile($application->citizen_back_path);
        $this->deleteFile($application->signature_path);
    }
}
