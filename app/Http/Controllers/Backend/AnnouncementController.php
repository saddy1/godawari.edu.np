<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::latest()->paginate(10);

        return view('backend.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('backend.announcements.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:notice,event,news',
            'category' => 'required|string|max:50',
            'content' => 'required',
            'image_type' => 'required|in:upload,link',
            'featured_image_media_path' => 'exclude_unless:image_type,upload|nullable|string|max:500',
            'image_file' => 'exclude_unless:image_type,upload|nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:5120',
            'image_link' => 'exclude_unless:image_type,link|required_if:image_type,link|url|max:2048',
        ]);

        $data = $request->except(['image_file', 'image_link', 'featured_image_media_path']);

        // Generate unique slug
        $data['slug'] = Str::slug($request->title).'-'.uniqid();

        // Handle Image
        if ($request->image_type === 'upload' && ($mediaPath = $this->selectedMediaPath($request))) {
            $data['featured_image'] = $mediaPath;
        } elseif ($request->image_type === 'upload' && $request->hasFile('image_file')) {
            $file = $request->file('image_file');
            // Create a unique filename
            $filename = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();

            // Move directly to public/announcements
            File::ensureDirectoryExists(public_path('announcements'));
            $file->move(public_path('announcements'), $filename);

            // Save the relative path in the database
            $data['featured_image'] = 'announcements/'.$filename;
        } elseif ($request->image_type === 'link' && $request->filled('image_link')) {
            $data['featured_image'] = $this->normalizeDriveLink($request->image_link);
        }

        Announcement::create($data);

        return redirect()->route('admin.announcements.index')->with('success', 'Post created successfully!');
    }

    public function edit(Announcement $announcement)
    {
        return view('backend.announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:notice,event,news',
            'category' => 'required|string|max:50',
            'content' => 'required',
            'image_type' => 'required|in:upload,link',
            'featured_image_media_path' => 'exclude_unless:image_type,upload|nullable|string|max:500',
            'image_file' => 'exclude_unless:image_type,upload|nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:5120',
            'image_link' => 'exclude_unless:image_type,link|required_if:image_type,link|url|max:2048',
        ]);

        $data = $request->except(['image_file', 'image_link', 'featured_image_media_path', '_token', '_method']);

        // Update Slug only if the title changed
        if ($request->title !== $announcement->title) {
            $data['slug'] = Str::slug($request->title).'-'.uniqid();
        }

        // Handle Image Update
        if ($request->image_type === 'upload' && ($mediaPath = $this->selectedMediaPath($request))) {
            $this->deleteAnnouncementFile($announcement->featured_image, $announcement->image_type);
            $data['featured_image'] = $mediaPath;
        } elseif ($request->image_type === 'upload' && $request->hasFile('image_file')) {

            // 1. Delete old image from public directory if it exists
            $this->deleteAnnouncementFile($announcement->featured_image, $announcement->image_type);

            // 2. Store new image directly in public/announcements
            $file = $request->file('image_file');
            $filename = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
            File::ensureDirectoryExists(public_path('announcements'));
            $file->move(public_path('announcements'), $filename);
            $data['featured_image'] = 'announcements/'.$filename;
        } elseif ($request->image_type === 'link' && $request->filled('image_link')) {
            $this->deleteAnnouncementFile($announcement->featured_image, $announcement->image_type);
            $data['featured_image'] = $this->normalizeDriveLink($request->image_link);
        } elseif ($request->image_type === 'upload' && $announcement->image_type === 'link') {
            $data['featured_image'] = null;
        }

        // If post type changed away from 'event', clear event fields
        if ($request->type !== 'event') {
            $data['event_date'] = null;
            $data['event_time'] = null;
            $data['event_location'] = null;
        }

        $announcement->update($data);

        return redirect()->route('admin.announcements.index')->with('success', 'Post updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Announcement $announcement)
    {
        // 1. Check if the image was an uploaded file (not a Drive link) and exists in DB
        $this->deleteAnnouncementFile($announcement->featured_image, $announcement->image_type);

        // 4. Delete the database record
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Post and its associated image were deleted successfully!');
    }

    /**
     * Force PDFs to open inline in the browser instead of downloading.
     */
    public function viewFile(Announcement $announcement)
    {
        // Check if it's a locally uploaded PDF
        if ($announcement->image_type === 'upload' && Str::endsWith(strtolower($announcement->featured_image), '.pdf')) {
            $path = public_path($announcement->featured_image);

            if (File::exists($path)) {
                // Return the file with headers forcing it to display in the browser
                return response()->file($path, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="'.basename($path).'"',
                ]);
            }
        }

        // If it's a regular image or a Drive link, just redirect to its normal URL
        return redirect($announcement->image_url);
    }

    private function selectedMediaPath(Request $request): ?string
    {
        $path = $request->input('featured_image_media_path');

        if (! $path) {
            return null;
        }

        return Media::where('file_path', $path)
            ->where(function ($query) {
                $query->where('mime_type', 'like', 'image/%')
                    ->orWhereIn('mime_type', ['application/pdf', 'application/x-pdf']);
            })
            ->value('file_path');
    }

    private function normalizeDriveLink(string $link): string
    {
        $parts = parse_url($link);

        if (($parts['host'] ?? null) !== 'drive.google.com') {
            return $link;
        }

        $fileId = null;
        if (preg_match('~/file/d/([^/]+)~', $parts['path'] ?? '', $matches)) {
            $fileId = $matches[1];
        } elseif (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
            $fileId = $query['id'] ?? null;
        }

        return $fileId
            ? 'https://drive.google.com/file/d/'.rawurlencode($fileId).'/view'
            : $link;
    }

    private function deleteAnnouncementFile(?string $path, ?string $imageType): void
    {
        if ($imageType !== 'upload' || ! $path || str_starts_with($path, 'media/')) {
            return;
        }

        $imagePath = public_path($path);
        if (File::exists($imagePath)) {
            File::delete($imagePath);
        }
    }
}
