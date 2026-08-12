<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Card\Student;
use App\Models\User;
use App\Services\MemberAccountService;
use App\Support\AdminPermissionMatrix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    // super-admin can assign all three; principal can only assign administrator
    const SUPER_ADMIN_ROLES = ['administrator', 'principal', 'accountant', 'store-keeper', 'librarian', 'technical'];
    const PRINCIPAL_ROLES   = ['administrator'];
    const ADMIN_ROLES       = ['administrator', 'principal', 'accountant', 'store-keeper', 'librarian', 'technical', 'super-admin'];

    private function assignableRoles(): array
    {
        return auth()->user()->isSuperAdmin()
            ? self::SUPER_ADMIN_ROLES
            : self::PRINCIPAL_ROLES;
    }

    public function index(Request $request): View
    {
        $this->ensureAdministrativeRolesExist();

        $perPage = in_array((int) $request->input('per_page', 10), [10, 20, 40, 50], true)
            ? (int) $request->input('per_page', 10)
            : 10;

        $admins = User::role(['administrator', 'super-admin', 'principal', 'accountant', 'store-keeper', 'librarian', 'technical'])
            ->with('student')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $assignableRoles = $this->assignableRoles();
        $selectedHrMember = $request->old('hr_member_id')
            ? Student::with('user.roles')->find($request->old('hr_member_id'))
            : null;

        return view('backend.admin-users.index', [
            'admins' => $admins,
            'assignableRoles' => $assignableRoles,
            'selectedHrMember' => $selectedHrMember ? $this->formatHrMemberSearchResult($selectedHrMember) : null,
            'perPage' => $perPage,
        ]);
    }

    public function searchHrMembers(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));

        $members = Student::query()
            ->with('user.roles')
            ->whereIn('member_type', ['teacher', 'staff'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('roll_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(12)
            ->get()
            ->map(fn (Student $member) => $this->formatHrMemberSearchResult($member))
            ->values();

        return response()->json(['results' => $members]);
    }

    public function store(Request $request): RedirectResponse
    {
        $assignable = $this->assignableRoles();

        $validated = $request->validate([
            'hr_member_id' => [
                'required',
                Rule::exists('students', 'id')->where(fn ($query) => $query->whereIn('member_type', ['teacher', 'staff'])),
            ],
            'role' => ['required', 'string', 'in:' . implode(',', $assignable)],
        ]);

        $member = Student::with('user')->findOrFail($validated['hr_member_id']);
        $user = app(MemberAccountService::class)->sync($member);

        if ($user->isSuperAdmin()) {
            return back()->withErrors(['hr_member_id' => 'Super admin accounts cannot be reassigned from here.']);
        }

        $user->forceFill([
            'status' => 1,
            'is_active' => true,
            'email_verified_at' => $user->email_verified_at ?: now(),
        ])->save();

        $this->syncAdministrativeRole($user, $validated['role']);

        return back()->with('success', $member->full_name . ' assigned as ' . ucfirst($validated['role']) . '.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $assignable = $this->assignableRoles();

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:' . implode(',', $assignable)],
        ]);

        if ($user->isSuperAdmin()) {
            return back()->withErrors(['role' => 'Super admin role cannot be changed.']);
        }

        // Principal cannot change another principal's role — only super-admin can
        if ($user->isPrincipal() && !auth()->user()->isSuperAdmin()) {
            return back()->withErrors(['role' => 'Only Super Admin can reassign a Principal account.']);
        }

        $this->syncAdministrativeRole($user, $validated['role']);

        return back()->with('success', $user->name . "'s role updated to " . ucfirst($validated['role']) . '.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            return back()->withErrors(['reset_password' => 'Cannot reset a Super Admin password.']);
        }

        $user->password = Hash::make($validated['password']);
        $user->saveQuietly();

        return back()->with('success', $user->name . "'s password has been reset successfully.");
    }

    public function permissions(User $user): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $this->ensurePermissionsExist();

        $modules = AdminPermissionMatrix::modules();
        $directPermissions = $user->permissions()->pluck('name')->all();
        $rolePermissions = $user->getAllPermissions()->pluck('name')->all();
        $usingRoleDefaults = count($directPermissions) === 0;

        return view('backend.admin-users.permissions', compact('user', 'modules', 'directPermissions', 'rolePermissions', 'usingRoleDefaults'));
    }

    public function updatePermissions(Request $request, User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        if ($user->isSuperAdmin()) {
            return back()->withErrors(['permissions' => 'Super Admin permissions cannot be restricted.']);
        }

        $this->ensurePermissionsExist();
        $allowed = AdminPermissionMatrix::names();

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', $allowed)],
        ]);

        $user->syncPermissions($validated['permissions'] ?? []);

        return redirect()
            ->route('admin.users.permissions', $user)
            ->with('success', $user->name . "'s custom permissions were updated.");
    }

    public function toggleSuperAdmin(User $user): RedirectResponse
    {
        // Only the system owner (APP_OWNER_EMAIL) may grant or revoke super-admin
        abort_unless(auth()->user()->isOwner(), 403);

        if ($user->is(auth()->user())) {
            return back()->withErrors(['super_admin' => 'You cannot change your own super-admin status.']);
        }

        if ($user->isSuperAdmin()) {
            // Revoke: remove super-admin, fall back to principal
            $user->removeRole('super-admin');
            $this->ensureAdministrativeRolesExist();
            if (! $user->hasAnyRole(self::ADMIN_ROLES)) {
                $user->assignRole('principal');
            }
            return back()->with('success', $user->name . ' has been downgraded from Super Admin to Principal.');
        }

        // Grant super-admin — they get full access but still cannot grant it to others
        $this->ensureAdministrativeRolesExist();
        $user->syncRoles(['super-admin']);

        return back()->with('success', $user->name . ' is now a Super Admin with full access.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->withErrors(['user' => 'You cannot remove your own account.']);
        }

        if ($user->isSuperAdmin()) {
            return back()->withErrors(['user' => 'Super admin accounts cannot be removed here.']);
        }

        // Principal cannot delete another principal
        if ($user->isPrincipal() && !auth()->user()->isSuperAdmin()) {
            return back()->withErrors(['user' => 'Only Super Admin can remove a Principal account.']);
        }

        $user->delete();

        return back()->with('success', 'Account removed successfully.');
    }

    private function ensurePermissionsExist(): void
    {
        $this->ensureAdministrativeRolesExist();

        foreach (AdminPermissionMatrix::names() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    private function ensureAdministrativeRolesExist(): void
    {
        foreach (self::ADMIN_ROLES as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $storePermissions = ['dashboard.admin', 'store.view', 'store.create', 'store.edit', 'store.approve', 'store.reports'];
        foreach ($storePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        Role::findByName('store-keeper', 'web')->syncPermissions($storePermissions);

        foreach (['librarian', 'technical'] as $role) {
            Role::findByName($role, 'web')->syncPermissions(['dashboard.admin']);
        }
    }

    private function syncAdministrativeRole(User $user, string $role): void
    {
        $this->ensureAdministrativeRolesExist();

        $roles = $user->roles
            ->pluck('name')
            ->reject(fn ($name) => in_array($name, self::ADMIN_ROLES, true))
            ->push($role)
            ->unique()
            ->values()
            ->all();

        $user->syncRoles($roles);
    }

    private function formatHrMemberSearchResult(Student $member): array
    {
        $adminRole = $member->user?->getRoleNames()?->first(fn ($role) => in_array($role, self::ADMIN_ROLES, true));
        $parts = array_filter([
            ucfirst($member->member_type),
            $member->roll_number,
            $member->mobile,
        ]);

        return [
            'id' => $member->id,
            'name' => $member->full_name,
            'meta' => implode(' · ', $parts),
            'admin_role' => $adminRole ? ucfirst(str_replace('-', ' ', $adminRole)) : null,
        ];
    }

}
