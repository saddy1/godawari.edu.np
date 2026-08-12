<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Setting;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class PrincipalController extends Controller
{
    public function index()
    {
        abort_unless(request()->user()?->isSuperAdmin(), 403);

        $settings = Setting::pluck('value', 'key')->toArray();

        return view('backend.principal.index', compact('settings'));
    }

    public function update(Request $request)
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        $request->validate([
            'principal_name'       => 'required|string|max:150',
            'principal_initials'   => 'required|string|max:5',
            'principal_role_en'    => 'required|string|max:255',
            'principal_role_ne'    => 'nullable|string|max:255',
            'principal_message_en' => 'required|string|max:255',
            'principal_message_ne' => 'nullable|string|max:255',
            'principal_quote_en'   => 'required|string|max:2000',
            'principal_quote_ne'   => 'nullable|string|max:2000',
            'home_principal_image_media_path' => 'nullable|string|max:500',
            'home_principal_image' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:4096',
        ]);

        $keys = [
            'principal_name',
            'principal_initials',
            'principal_role_en',
            'principal_role_ne',
            'principal_message_en',
            'principal_message_ne',
            'principal_quote_en',
            'principal_quote_ne',
        ];

        foreach ($request->only($keys) as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        if ($mediaPath = $this->selectedMediaPath($request, 'home_principal_image_media_path')) {
            $this->setPrincipalImagePath($mediaPath);
        } elseif ($request->hasFile('home_principal_image')) {
            $this->replacePrincipalImage($request);
        }

        app(SiteSettings::class)->clearCache();

        return back()->with('success', 'Principal settings saved successfully.');
    }

    private function replacePrincipalImage(Request $request): void
    {
        $file = $request->file('home_principal_image');
        $filename = 'home-principal-image.' . strtolower($file->getClientOriginalExtension());
        $relativePath = 'uploads/site/' . $filename;
        $targetPath = public_path($relativePath);

        File::ensureDirectoryExists(public_path('uploads/site'));

        $oldPath = Setting::where('key', 'home_principal_image')->value('value');
        if ($oldPath && str_starts_with($oldPath, 'uploads/site/')) {
            $oldFullPath = public_path($oldPath);
            if (File::exists($oldFullPath)) {
                File::delete($oldFullPath);
            }
        }

        if (File::exists($targetPath)) {
            File::delete($targetPath);
        }

        $file->move(public_path('uploads/site'), $filename);
        Setting::updateOrCreate(['key' => 'home_principal_image'], ['value' => $relativePath]);
    }

    private function selectedMediaPath(Request $request, string $field): ?string
    {
        $path = $request->input($field);

        if (! $path) {
            return null;
        }

        return Media::where('file_path', $path)
            ->where('mime_type', 'like', 'image/%')
            ->value('file_path');
    }

    private function setPrincipalImagePath(string $relativePath): void
    {
        Setting::updateOrCreate(['key' => 'home_principal_image'], ['value' => $relativePath]);
    }
}
