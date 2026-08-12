<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CmsPageController extends Controller
{
    public function index()
    {
        $pages = CmsPage::with('parent')->orderBy('sort_order')->orderBy('title')->get();

        return view('backend.cms.pages.index', compact('pages'));
    }

    public function create()
    {
        $page = new CmsPage(['status' => 'draft', 'template' => 'default', 'sort_order' => 0]);
        $parents = CmsPage::orderBy('title')->get(['id', 'title']);

        return view('backend.cms.pages.form', compact('page', 'parents'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;
        $data['content_blocks'] = $this->normalizedBlocks($request);
        $data['content_blocks_ne'] = $this->normalizedBlocks($request, 'content_blocks_ne');
        $data['content_html'] = null;
        $data['published_at'] = $data['status'] === 'published' ? now() : null;
        $data['featured_image'] = $request->input('featured_image') ?: $this->storeImage($request);

        CmsPage::create($data);

        return redirect()->route('admin.cms.pages.index')->with('success', 'Page created.');
    }

    public function edit(CmsPage $page)
    {
        $parents = CmsPage::whereKeyNot($page->id)->orderBy('title')->get(['id', 'title']);

        return view('backend.cms.pages.form', compact('page', 'parents'));
    }

    public function update(Request $request, CmsPage $page)
    {
        $data = $this->validated($request, $page);
        $data['content_blocks'] = $this->normalizedBlocks($request);
        $data['content_blocks_ne'] = $this->normalizedBlocks($request, 'content_blocks_ne');
        $data['content_html'] = null;
        $data['published_at'] = $data['status'] === 'published'
            ? ($page->published_at ?: now())
            : null;

        if ($request->hasFile('featured_image_file')) {
            $this->deleteImage($page->featured_image);
            $data['featured_image'] = $this->storeImage($request);
        } elseif ($request->has('featured_image')) {
            $data['featured_image'] = $request->input('featured_image');
        }

        $page->update($data);

        return redirect()->route('admin.cms.pages.index')->with('success', 'Page updated.');
    }

    public function destroy(CmsPage $page)
    {
        $this->deleteImage($page->featured_image);
        $page->delete();

        return back()->with('success', 'Page deleted.');
    }

    private function validated(Request $request, ?CmsPage $page = null): array
    {
        $id = $page?->id ?: 'NULL';

        $data = $request->validate([
            'parent_id' => 'nullable|exists:cms_pages,id',
            'title' => 'required|string|max:255',
            'title_ne' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|alpha_dash|unique:cms_pages,slug,'.$id,
            'status' => 'required|in:draft,published',
            'template' => 'required|in:default,wide',
            'meta_title' => 'nullable|string|max:255',
            'meta_title_ne' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_description_ne' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'meta_keywords_ne' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'featured_image' => 'nullable|string|max:500',
            'featured_image_file' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    private function normalizedBlocks(Request $request, string $field = 'content_blocks'): array
    {
        $blocks = json_decode($request->input($field, '[]'), true);
        if (! is_array($blocks)) {
            return [];
        }

        return collect($blocks)
            ->filter(fn ($block) => filled($block['type'] ?? null))
            ->values()
            ->all();
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('featured_image_file')) {
            return null;
        }

        $file = $request->file('featured_image_file');
        $filename = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'_'.uniqid().'.'.$file->getClientOriginalExtension();
        File::ensureDirectoryExists(public_path('media'));
        $file->move(public_path('media'), $filename);

        $path = 'media/'.$filename;

        Media::create([
            'name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => filesize(public_path($path)),
            'category' => 'CMS',
        ]);

        return $path;
    }

    private function deleteImage(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/cms/pages/') && File::exists(public_path($path))) {
            File::delete(public_path($path));
        }
    }
}
