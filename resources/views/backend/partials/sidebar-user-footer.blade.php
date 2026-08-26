{{-- Shared settings and account controls for every ERP module sidebar. --}}
<div x-data="{ systemSettingsOpen: false }" class="sidebar-footer shrink-0 border-t px-3 py-3"
     style="border-color:rgba(255,255,255,.08);background:rgba(0,0,0,.3)">
    @if(($showSidebarSystemSettings ?? true) && auth()->user()?->isSuperAdmin())
        <button type="button" @click="systemSettingsOpen = !systemSettingsOpen"
                class="mb-3 flex w-full items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-left text-[11px] font-extrabold text-white/75 transition-colors hover:border-white/20 hover:bg-white/10 hover:text-white"
                :aria-expanded="systemSettingsOpen">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="flex-1">System Settings</span>
            <svg class="h-3.5 w-3.5 transition-transform" :class="systemSettingsOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-show="systemSettingsOpen" x-transition class="mb-3 max-h-52 space-y-1 overflow-y-auto" style="display:none;">
            @foreach([
                ['Staff Roles', 'admin.users.index', 'admin.users.*'],
                ['Module Access', 'admin.modules.index', 'admin.modules.*'],
                ['SEO Optimizer', 'admin.seo.index', 'admin.seo.*'],
                ['Principal Info', 'admin.principal.index', 'admin.principal.*'],
                ['Site Configuration', 'admin.settings.index', 'admin.settings.*'],
                ['Administration Setup', 'settings.index', 'settings.*'],
            ] as [$settingsLabel, $settingsRoute, $settingsPattern])
                <a href="{{ route($settingsRoute) }}"
                   class="block rounded-lg px-3 py-1.5 text-[11px] font-semibold transition-colors {{ request()->routeIs($settingsPattern) ? 'bg-white/15 text-white' : 'text-white/55 hover:bg-white/10 hover:text-white' }}">
                    {{ $settingsLabel }}
                </a>
            @endforeach
        </div>
    @endif

    <div class="flex items-center gap-2.5">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 text-xs font-extrabold text-white"
             style="background-color:var(--theme-primary,#1a5632);border-color:var(--theme-secondary,#e2a024)">
            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-xs font-extrabold leading-tight text-white">{{ auth()->user()->name ?? 'User' }}</p>
            <p class="mt-0.5 truncate text-[10px] font-medium leading-tight text-white/40">{{ auth()->user()->role_label ?? 'User' }}</p>
        </div>
    </div>

    <div class="mt-3 grid grid-cols-2 gap-2">
        <a href="{{ route('account.password.edit') }}"
           class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-white/10 bg-white/5 px-2 py-2 text-[11px] font-bold text-white/70 transition-colors hover:border-white/20 hover:bg-white/10 hover:text-white">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            Password
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-white/10 bg-white/5 px-2 py-2 text-[11px] font-bold text-white/70 transition-colors hover:border-red-400/30 hover:bg-red-500/15 hover:text-red-200">
                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Logout
            </button>
        </form>
    </div>
</div>
