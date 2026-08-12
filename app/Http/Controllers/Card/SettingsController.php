<?php

namespace App\Http\Controllers\Card;

use App\Http\Controllers\Controller;

use App\Models\Card\CardBackground;
use App\Models\Card\Organization;
use App\Models\Card\OrgAsset;
use App\Models\Card\Department;
use App\Models\Card\Section;
use App\Models\Card\MemberType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $masterDataAvailable = Schema::hasTable('organizations')
            && Schema::hasTable('departments')
            && Schema::hasTable('sections')
            && Schema::hasTable('member_types');

        $organizations = $masterDataAvailable
            ? Organization::withCount(['departments', 'memberTypes'])->orderBy('name')->get()
            : collect();
        $tab           = $request->get('tab', 'organizations');
        $orgId         = $request->get('org');
        $deptId        = $request->get('dept');

        $selectedOrg = $masterDataAvailable && $orgId
            ? Organization::with('departments.sections', 'memberTypes')->find($orgId)
            : null;
        $selectedDept = $masterDataAvailable && $deptId
            ? Department::with('sections')->find($deptId)
            : null;

        $assets = $masterDataAvailable && Schema::hasTable('org_assets')
            ? OrgAsset::orderBy('type')->orderBy('name')->get()
            : collect();

        $cardBackgrounds = Schema::hasTable('card_backgrounds')
            ? CardBackground::orderBy('org_type')->orderBy('member_type')->orderBy('name')->get()
            : collect();

        return view('card.settings.index', compact('organizations', 'tab', 'selectedOrg', 'selectedDept', 'masterDataAvailable', 'assets', 'cardBackgrounds'));
    }

    // ── Organizations ──────────────────────────────────────────────────────

    public function storeOrganization(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:150',
            'slug'               => 'required|string|max:50|unique:organizations,slug|alpha_dash',
            'type'               => 'required|in:college,school,other',
            'is_active'          => 'boolean',
            'logo_asset_id'      => 'nullable|exists:org_assets,id',
            'signature_asset_id' => 'nullable|exists:org_assets,id',
            'stamp_asset_id'     => 'nullable|exists:org_assets,id',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $organization = Organization::create($data);
        Cache::forget("card_org_{$organization->slug}");
        return back()->with('success', "Organization '{$data['name']}' created.");
    }

    public function updateOrganization(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:150',
            'slug'               => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('organizations','slug')->ignore($organization->id)],
            'type'               => 'required|in:college,school,other',
            'is_active'          => 'boolean',
            'logo_asset_id'      => 'nullable|exists:org_assets,id',
            'signature_asset_id' => 'nullable|exists:org_assets,id',
            'stamp_asset_id'     => 'nullable|exists:org_assets,id',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $oldSlug = $organization->slug;
        $organization->update($data);
        Cache::forget("card_org_{$oldSlug}");
        Cache::forget("card_org_{$organization->slug}");
        return back()->with('success', "Organization updated.");
    }

    public function destroyOrganization(Organization $organization)
    {
        Cache::forget("card_org_{$organization->slug}");
        $organization->delete();
        return redirect()->route('settings.index')->with('success', "Organization deleted.");
    }

    // ── Departments ────────────────────────────────────────────────────────

    public function storeDepartment(Request $request)
    {
        $data = $request->validate([
            'organization_id'    => 'required|exists:organizations,id',
            'name'               => 'required|string|max:150',
            'university'         => 'nullable|string|max:200',
            'university_college' => 'nullable|string|max:200',
            'university_logo'    => 'nullable|string|max:255',
            'is_active'          => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        Department::create($data);
        return back()->with('success', "Department '{$data['name']}' added.");
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:150',
            'university'         => 'nullable|string|max:200',
            'university_college' => 'nullable|string|max:200',
            'university_logo'    => 'nullable|string|max:255',
            'is_active'          => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $department->update($data);
        return back()->with('success', "Department updated.");
    }

    public function destroyDepartment(Department $department)
    {
        $orgId = $department->organization_id;
        $department->delete();
        return redirect()->route('settings.index', ['tab' => 'departments', 'org' => $orgId])
            ->with('success', "Department deleted.");
    }

    // ── Sections ───────────────────────────────────────────────────────────

    public function storeSection(Request $request)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name'          => 'required|string|max:100',
            'is_active'     => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        Section::create($data);
        return back()->with('success', "Section '{$data['name']}' added.");
    }

    public function updateSection(Request $request, Section $section)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $section->update($data);
        return back()->with('success', "Section updated.");
    }

    public function destroySection(Section $section)
    {
        $deptId = $section->department_id;
        $dept   = $section->department;
        $section->delete();
        return redirect()->route('settings.index', ['tab' => 'sections', 'org' => $dept->organization_id, 'dept' => $deptId])
            ->with('success', "Section deleted.");
    }

    // ── Member Types ───────────────────────────────────────────────────────

    public function storeMemberType(Request $request)
    {
        $data = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name'            => 'required|string|max:100',
            'is_active'       => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        MemberType::create($data);
        return back()->with('success', "Member type '{$data['name']}' added.");
    }

    public function updateMemberType(Request $request, MemberType $memberType)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $memberType->update($data);
        return back()->with('success', "Member type updated.");
    }

    public function destroyMemberType(MemberType $memberType)
    {
        $memberType->delete();
        return back()->with('success', "Member type deleted.");
    }

    // ── Org Assets (shared library) ────────────────────────────────────────

    public function storeAsset(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'type' => 'required|in:logo,signature,stamp',
            'file' => 'required|image|max:1024',
        ]);
        File::ensureDirectoryExists(public_path('img/org'));
        $file     = $request->file('file');
        $filename = \Str::slug($request->name) . '_' . time() . '.' . $file->getClientOriginalExtension();
        $destPath = public_path('img/org/' . $filename);
        $file->move(public_path('img/org'), $filename);
        $this->resizeImage($destPath, 400);
        OrgAsset::create(['name' => $request->name, 'type' => $request->type, 'path' => 'img/org/' . $filename]);
        return back()->with('success', "Asset '{$request->name}' uploaded.");
    }

    public function destroyAsset(OrgAsset $orgAsset)
    {
        // Prevent delete if any org is still using it
        $inUse = Organization::where('logo_asset_id', $orgAsset->id)
            ->orWhere('signature_asset_id', $orgAsset->id)
            ->orWhere('stamp_asset_id', $orgAsset->id)
            ->exists();
        if ($inUse) {
            return back()->with('error', "Cannot delete: asset is still assigned to one or more organizations.");
        }
        if (File::exists(public_path($orgAsset->path))) {
            File::delete(public_path($orgAsset->path));
        }
        $orgAsset->delete();
        return back()->with('success', "Asset deleted.");
    }

    // ── Card Backgrounds ──────────────────────────────────────────────────

    public function storeBackground(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:150',
            'org_type'    => 'required|string|max:50',
            'member_type' => 'required|in:student,staff,teacher',
            'file'        => 'required|image|max:2048',
        ]);

        $dir = public_path('erp/card/img/bg');
        File::ensureDirectoryExists($dir);

        $file     = $request->file('file');
        $filename = \Str::slug($request->name) . '_' . time() . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $filename);

        CardBackground::where('org_type', $request->org_type)
            ->where('member_type', $request->member_type)
            ->update(['is_active' => false]);

        CardBackground::create([
            'name'        => $request->name,
            'org_type'    => $request->org_type,
            'member_type' => $request->member_type,
            'file_path'   => 'erp/card/img/bg/' . $filename,
            'is_active'   => true,
        ]);

        Cache::forget("card_bg_{$request->org_type}_{$request->member_type}");

        return back()->with('success', "Background '{$request->name}' uploaded and activated.");
    }

    public function activateBackground(CardBackground $cardBackground)
    {
        CardBackground::where('org_type', $cardBackground->org_type)
            ->where('member_type', $cardBackground->member_type)
            ->update(['is_active' => false]);

        $cardBackground->update(['is_active' => true]);

        Cache::forget("card_bg_{$cardBackground->org_type}_{$cardBackground->member_type}");

        return back()->with('success', "'{$cardBackground->name}' is now the active background for {$cardBackground->org_type} / {$cardBackground->member_type}.");
    }

    public function destroyBackground(CardBackground $cardBackground)
    {
        Cache::forget("card_bg_{$cardBackground->org_type}_{$cardBackground->member_type}");

        if (File::exists(public_path($cardBackground->file_path))) {
            File::delete(public_path($cardBackground->file_path));
        }
        $cardBackground->delete();
        return back()->with('success', "Background deleted.");
    }

    // ── AJAX helpers ───────────────────────────────────────────────────────

    public function departments(Organization $organization)
    {
        return response()->json(
            $organization->activeDepartments()->get(['id', 'name'])
        );
    }

    public function sectionsForDept(Department $department)
    {
        return response()->json(
            $department->activeSections()->get(['id', 'name'])
        );
    }

    private function resizeImage(string $path, int $maxDim): void
    {
        $info = @getimagesize($path);
        if (!$info || ($info[0] <= $maxDim && $info[1] <= $maxDim)) return;

        $ratio = min($maxDim / $info[0], $maxDim / $info[1]);
        $newW  = (int) ($info[0] * $ratio);
        $newH  = (int) ($info[1] * $ratio);

        $src = match ($info[2]) {
            IMAGETYPE_PNG  => imagecreatefrompng($path),
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default        => null,
        };
        if (!$src) return;

        $dst = imagecreatetruecolor($newW, $newH);
        if ($info[2] === IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $info[0], $info[1]);

        match ($info[2]) {
            IMAGETYPE_PNG  => imagepng($dst, $path, 7),
            default        => imagejpeg($dst, $path, 85),
        };
        imagedestroy($src);
        imagedestroy($dst);
    }
}
