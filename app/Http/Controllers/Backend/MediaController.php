<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class MediaController extends Controller
{
    // Fetch images for the CMS media picker.
    public function index(Request $request)
    {
        $media = Media::query()
            ->when(
                $request->input('mode') === 'document',
                fn ($query) => $query->where(function ($query) {
                    $query->where('mime_type', 'like', 'image/%')
                        ->orWhereIn('mime_type', ['application/pdf', 'application/x-pdf']);
                }),
                fn ($query) => $query->where('mime_type', 'like', 'image/%'),
            )
            ->latest()
            ->get();

        return response()->json($media);
    }

    // Handle AJAX file uploads
    public function store(Request $request)
    {
        $acceptsDocuments = $request->input('mode') === 'document';

        $request->validate([
            'file' => $acceptsDocuments
                ? 'required|file|mimes:jpeg,png,jpg,webp,pdf|max:5120'
                : 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $file = $request->file('file');
        
        // 1. Generate a clean, unique filename
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '_' . Str::slug($originalName) . '.' . $extension;
        
        // 2. Define the destination inside the public folder
        $destinationPath = public_path('media');

        // Ensure the directory exists
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }
        
        // 3. Move the file directly to public/media
        $file->move($destinationPath, $filename);

        // The path we save to the database (relative to the public folder)
        $dbPath = 'media/' . $filename;

        // 4. Save to database
        $media = Media::create([
            'name' => $file->getClientOriginalName(),
            'file_path' => $dbPath,
            'mime_type' => $file->getClientMimeType(), // Use getClientMimeType() for moved files
            'size' => filesize(public_path($dbPath)),   // Get size from the newly moved file
        ]);

        return response()->json([
            'message' => 'File uploaded successfully to public folder',
            'media' => $media
        ]);
    }

    // Add this to the top of your controller if it isn't there already:
    // use Illuminate\Support\Facades\File;
    // use Illuminate\Support\Str;

    /**
     * Display the Admin Gallery Page
     */
    public function gallery()
    {
        $hasGallerySelection = Schema::hasColumn('media', 'show_in_gallery');
        $imageQuery = Media::where('mime_type', 'like', 'image/%');

        $selectedImages = $hasGallerySelection
            ? (clone $imageQuery)->where('show_in_gallery', true)->latest()->get()
            : collect();

        $availableImages = $hasGallerySelection
            ? (clone $imageQuery)->where('show_in_gallery', false)->latest()->take(24)->get()
            : (clone $imageQuery)->latest()->take(24)->get();

        return view('backend.gallery.index', compact('selectedImages', 'availableImages', 'hasGallerySelection'));
    }

    /**
     * Display the full Media Library page.
     */
    public function library()
    {
        $media = Media::latest()->paginate(24);
        return view('backend.media.index', compact('media'));
    }

    /**
     * Upload images directly into the public gallery selection.
     */
    public function uploadGallery(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'category' => 'required|string|max:100',
        ]);

        $this->storeUploadedFiles($request, true);

        return back()->with('success', 'Gallery images uploaded and selected for the public gallery.');
    }

    /**
     * Select existing Media Library images for the public gallery.
     */
    public function selectForGallery(Request $request)
    {
        if (! Schema::hasColumn('media', 'show_in_gallery')) {
            return back()->withErrors(['gallery' => 'Run the latest migration before selecting gallery images.']);
        }

        $request->validate([
            'media_ids' => 'required|array',
            'media_ids.*' => 'integer|exists:media,id',
        ]);

        Media::whereIn('id', $request->input('media_ids', []))
            ->where('mime_type', 'like', 'image/%')
            ->update(['show_in_gallery' => true]);

        return back()->with('success', 'Selected media images added to the gallery.');
    }

    /**
     * Handle Multiple File Uploads from the Gallery Page
     */
  public function uploadMultiple(Request $request)
{
    $request->validate([
        'files' => 'required|array',
        'files.*' => 'required|file|mimes:jpeg,png,jpg,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv|max:10240',
        'category' => 'required|string|max:100',
        'show_in_gallery' => 'nullable|boolean',
    ]);

    $this->storeUploadedFiles($request, false);

    return back()->with('success', 'Media uploaded successfully to the ' . $request->category . ' category.');
}

    private function storeUploadedFiles(Request $request, bool $showImagesInGallery): void
    {
        if (! $request->hasFile('files')) {
            return;
        }

        $destinationPath = public_path('media');

        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        foreach ($request->file('files') as $file) {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . \Illuminate\Support\Str::slug($originalName) . '_' . uniqid() . '.' . $extension;
            
            $file->move($destinationPath, $filename);
            $dbPath = 'media/' . $filename;

            $data = [
                'name' => $file->getClientOriginalName(),
                'file_path' => $dbPath,
                'mime_type' => $file->getClientMimeType(),
                'size' => filesize(public_path($dbPath)),
                'category' => $request->category, // <-- Save the chosen category
            ];

            if (Schema::hasColumn('media', 'show_in_gallery')) {
                $data['show_in_gallery'] = $showImagesInGallery && str_starts_with((string) $file->getClientMimeType(), 'image/');
            }

            Media::create($data);
        }
    }
    /**
     * Delete an Image from Storage and Database
     */
    public function destroy(Media $media)
    {
        // 1. Delete the physical file from the public folder
        $filePath = public_path($media->file_path);
        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        // 2. Delete the database record
        $media->delete();

        return back()->with('success', 'Image permanently deleted.');
    }

    /**
     * Update media details.
     */
    public function update(Request $request, Media $media)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'caption' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'show_in_gallery' => 'nullable|boolean',
        ]);

        $media->name = $request->input('name');
        $media->caption = $request->input('caption');
        $media->category = $request->input('category') ?: 'Campus';
        if (Schema::hasColumn('media', 'show_in_gallery')) {
            $media->show_in_gallery = $request->boolean('show_in_gallery') && str_starts_with((string) $media->mime_type, 'image/');
        }
        $media->save();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Media updated', 'media' => $media]);
        }

        return back()->with('success', 'Media updated successfully.');
    }
}
