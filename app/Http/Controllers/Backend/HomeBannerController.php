<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\HomeBanner;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class HomeBannerController extends Controller
{
    public function index()
    {
        $banners = HomeBanner::orderBy('sort_order')->latest()->get();

        return view('backend.home-banners.index', compact('banners'));
    }

    public function create()
    {
        $banner = new HomeBanner([
            'text_position' => 'left',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        return view('backend.home-banners.form', compact('banner'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['image_path'] = $this->selectedMediaPath($request) ?: $this->storeImage($request);
        unset($data['image']);
        unset($data['selected_image_path']);

        HomeBanner::create($data);

        return redirect()->route('admin.home-banners.index')->with('success', 'Banner created successfully.');
    }

    public function edit(HomeBanner $homeBanner)
    {
        $banner = $homeBanner;

        return view('backend.home-banners.form', compact('banner'));
    }

    public function update(Request $request, HomeBanner $homeBanner)
    {
        $data = $this->validated($request, false);
        $data['is_active'] = $request->boolean('is_active');

        if ($mediaPath = $this->selectedMediaPath($request)) {
            $this->deleteImage($homeBanner->image_path);
            $data['image_path'] = $mediaPath;
        } elseif ($request->hasFile('image')) {
            $this->deleteImage($homeBanner->image_path);
            $data['image_path'] = $this->storeImage($request);
        }
        unset($data['image']);
        unset($data['selected_image_path']);

        $homeBanner->update($data);

        return redirect()->route('admin.home-banners.index')->with('success', 'Banner updated successfully.');
    }

    public function destroy(HomeBanner $homeBanner)
    {
        $this->deleteImage($homeBanner->image_path);
        $homeBanner->delete();

        return back()->with('success', 'Banner deleted successfully.');
    }

    public function toggle(HomeBanner $homeBanner)
    {
        $homeBanner->update(['is_active' => ! $homeBanner->is_active]);

        return back()->with('success', 'Banner status updated.');
    }

    private function validated(Request $request, bool $imageRequired = true): array
    {
        return $request->validate([
            'eyebrow'            => 'nullable|string|max:255',
            'eyebrow_ne'         => 'nullable|string|max:255',
            'title'              => 'required|string|max:255',
            'title_ne'           => 'nullable|string|max:255',
            'subtitle'           => 'nullable|string|max:1000',
            'subtitle_ne'        => 'nullable|string|max:1000',
            'primary_label'      => 'nullable|string|max:100',
            'primary_label_ne'   => 'nullable|string|max:100',
            'primary_url'        => 'nullable|string|max:500',
            'secondary_label'    => 'nullable|string|max:100',
            'secondary_label_ne' => 'nullable|string|max:100',
            'secondary_url'      => 'nullable|string|max:500',
            'text_position'      => 'required|in:left,center',
            'sort_order'         => 'nullable|integer|min:0|max:999',
            'selected_image_path' => 'nullable|string|max:500',
            'image'              => [
                $imageRequired && ! $request->filled('selected_image_path') ? 'required' : 'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:6144',
            ],
        ]);
    }

    private function selectedMediaPath(Request $request): ?string
    {
        $path = $request->input('selected_image_path');

        if (! $path) {
            return null;
        }

        return Media::where('file_path', $path)
            ->where('mime_type', 'like', 'image/%')
            ->value('file_path');
    }

    private function storeImage(Request $request): string
    {
        $image = $request->file('image');
        $filename = 'banner-'.time().'-'.Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$image->getClientOriginalExtension();
        File::ensureDirectoryExists(public_path('uploads/home-banners'));
        $image->move(public_path('uploads/home-banners'), $filename);

        return 'uploads/home-banners/'.$filename;
    }

    private function deleteImage(?string $path): void
    {
        if (! $path || str_starts_with($path, 'assets/')) {
            return;
        }

        $fullPath = public_path($path);
        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }
    }
}
