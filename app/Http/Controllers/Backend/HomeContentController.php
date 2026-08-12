<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\HomeContent;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class HomeContentController extends Controller
{
    public function index()
    {
        $items = HomeContent::orderBy('type')->orderBy('sort_order')->orderBy('title')->get();

        return view('backend.home-content.index', compact('items'));
    }

    public function create()
    {
        $item = new HomeContent([
            'type' => 'quick_link',
            'icon_key' => 'notice',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $icons = HomeContent::ICONS;

        return view('backend.home-content.form', compact('item', 'icons'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['image_path'] = $this->selectedMediaPath($request) ?: $this->storeImage($request);
        unset($data['selected_image_path']);

        HomeContent::create($data);

        return redirect()->route('admin.home-content.index')->with('success', 'Homepage content created.');
    }

    public function edit(HomeContent $homeContent)
    {
        $item = $homeContent;
        $icons = HomeContent::ICONS;

        return view('backend.home-content.form', compact('item', 'icons'));
    }

    public function update(Request $request, HomeContent $homeContent)
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');

        if ($mediaPath = $this->selectedMediaPath($request)) {
            $this->deleteImage($homeContent->image_path);
            $data['image_path'] = $mediaPath;
        } elseif ($request->hasFile('image')) {
            $this->deleteImage($homeContent->image_path);
            $data['image_path'] = $this->storeImage($request);
        }
        unset($data['selected_image_path']);

        $homeContent->update($data);

        return redirect()->route('admin.home-content.index')->with('success', 'Homepage content updated.');
    }

    public function destroy(HomeContent $homeContent)
    {
        $this->deleteImage($homeContent->image_path);
        $homeContent->delete();

        return back()->with('success', 'Homepage content deleted.');
    }

    public function toggle(HomeContent $homeContent)
    {
        $homeContent->update(['is_active' => ! $homeContent->is_active]);

        return back()->with('success', 'Homepage content status updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type'        => 'required|in:quick_link,learning_pathway',
            'category'    => 'nullable|string|max:80',
            'title'       => 'required|string|max:255',
            'title_ne'    => 'nullable|string|max:255',
            'subtitle'    => 'nullable|string|max:255',
            'subtitle_ne' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1200',
            'url'         => 'nullable|string|max:500',
            'icon_key'    => 'required|in:'.implode(',', array_keys(HomeContent::ICONS)),
            'sort_order'  => 'nullable|integer|min:0|max:999',
            'selected_image_path' => 'nullable|string|max:500',
            'image'       => 'nullable|file|mimes:jpg,jpeg,png,webp|max:4096',
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

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $image = $request->file('image');
        $filename = 'home-content-'.time().'-'.Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$image->getClientOriginalExtension();
        File::ensureDirectoryExists(public_path('uploads/home-content'));
        $image->move(public_path('uploads/home-content'), $filename);

        return 'uploads/home-content/'.$filename;
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
