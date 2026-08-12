<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\PopupNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class PopupNoticeController extends Controller
{
    public function index()
    {
        // Fetch newest popups first
        $popups = PopupNotice::latest()->get();
        $popups->each(fn (PopupNotice $popup) => $this->syncPopupImageToMedia($popup));

        return view('backend.popups.index', compact('popups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image_media_path' => 'nullable|string|max:500',
            'image' => [$request->filled('image_media_path') ? 'nullable' : 'required', 'image', 'max:5120'],
            'link_url' => 'nullable|url'
        ]);

        if ($request->has('is_active') && PopupNotice::where('is_active', true)->count() >= 3) {
            return back()->withErrors(['error' => 'You can only have a maximum of 3 active popups at a time. Disable an old one first.']);
        }

        $imagePath = $this->selectedMediaPath($request) ?: $this->storePopupImage($request);

        $popup = PopupNotice::create([
            'title' => $request->title,
            'image_path' => $imagePath,
            'link_url' => $request->link_url,
            'is_active' => $request->has('is_active')
        ]);

        $this->syncPopupImageToMedia($popup);

        return back()->with('success', 'Popup notice added successfully.');
    }

    // NEW: Edit Method
    public function edit(PopupNotice $popup)
    {
        return view('backend.popups.edit', compact('popup'));
    }

    // NEW: Update Method
    public function update(Request $request, PopupNotice $popup)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image_media_path' => 'nullable|string|max:500',
            'image' => 'nullable|image|max:5120', // Image is optional on update
            'link_url' => 'nullable|url'
        ]);

        $isActive = $request->has('is_active');

        // Check limit only if turning from Inactive -> Active
        if ($isActive && !$popup->is_active && PopupNotice::where('is_active', true)->count() >= 3) {
            return back()->withErrors(['error' => 'Maximum 3 active popups allowed. Disable an old one first.']);
        }

        $data = [
            'title' => $request->title,
            'link_url' => $request->link_url,
            'is_active' => $isActive,
        ];

        if ($mediaPath = $this->selectedMediaPath($request)) {
            $this->deletePopupImageAndMedia($popup->image_path);
            $data['image_path'] = $mediaPath;
        } elseif ($request->hasFile('image')) {
            // 1. Delete old image
            $this->deletePopupImageAndMedia($popup->image_path);

            // 2. Save new image
            $data['image_path'] = $this->storePopupImage($request);
        }

        $popup->update($data);
        $this->syncPopupImageToMedia($popup->fresh());

        return redirect()->route('admin.popups.index')->with('success', 'Popup updated successfully.');
    }

    public function destroy(PopupNotice $popup)
    {
        $this->deletePopupImageAndMedia($popup->image_path);
        $popup->delete();
        return back()->with('success', 'Popup deleted permanently.');
    }

    public function toggle(PopupNotice $popup)
    {
        if (!$popup->is_active && PopupNotice::where('is_active', true)->count() >= 3) {
            return back()->withErrors(['error' => 'Maximum 3 active popups allowed.']);
        }

        $popup->update(['is_active' => !$popup->is_active]);
        return back()->with('success', 'Popup status updated.');
    }

    private function syncPopupImageToMedia(?PopupNotice $popup): void
    {
        if (! $popup || blank($popup->image_path) || str_starts_with($popup->image_path, 'media/')) {
            return;
        }

        $path = public_path($popup->image_path);
        if (! File::exists($path)) {
            return;
        }

        Media::updateOrCreate(
            ['file_path' => $popup->image_path],
            [
                'name' => $popup->title,
                'mime_type' => File::mimeType($path),
                'size' => File::size($path),
                'category' => 'Popups',
            ]
        );
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

    private function storePopupImage(Request $request): string
    {
        $file = $request->file('image');
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $destinationPath = public_path('popups');

        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        $file->move($destinationPath, $filename);

        return 'popups/' . $filename;
    }

    private function deletePopupImageAndMedia(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (str_starts_with($path, 'media/')) {
            return;
        }

        $filePath = public_path($path);
        if (File::exists($filePath)) {
            File::delete($filePath);
        }
        Media::where('file_path', $path)->delete();
    }
}
