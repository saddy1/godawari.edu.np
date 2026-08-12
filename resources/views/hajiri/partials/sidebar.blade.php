{{-- Mobile backdrop --}}
<div x-show="sidebarOpen"
     x-transition.opacity
     @click="sidebarOpen = false"
     class="fixed inset-0 z-40 bg-gray-900/70 backdrop-blur-sm lg:hidden"
     style="display:none;"></div>

{{-- Sidebar --}}
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       @click.capture="if ($event.target.closest('a')) sidebarOpen = false"
       class="fixed inset-y-0 left-0 z-50 w-60 text-white transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto flex flex-col h-dvh border-r shrink-0"
       style="background: linear-gradient(180deg, var(--theme-sidebar-bg, #0b2415) 0%, var(--theme-sidebar-gradient-end, #050f09) 100%); border-color: rgba(255,255,255,0.08);">

    {{-- Brand --}}
    <div class="flex items-center justify-between h-14 px-4 border-b shrink-0" style="border-color: rgba(255,255,255,0.08); background: rgba(0,0,0,0.25);">
        <a href="{{ route('hajiri.home') }}" class="flex items-center gap-2.5 min-w-0">
            <div class="w-7 h-7 bg-white/90 rounded-lg flex items-center justify-center p-1 shrink-0">
                <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-white leading-none truncate">{{ $siteSettings->get('app_name', 'School ERP') }}</p>
                <p class="text-[9px] uppercase tracking-widest font-semibold mt-0.5" style="color: var(--theme-secondary, #e2a024);">Hajiri Module</p>
            </div>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden p-1 text-white/40 hover:text-white rounded-md hover:bg-white/10 transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">

        @php
            $active = fn(string ...$patterns) => collect($patterns)->contains(fn($p) => request()->is($p) || request()->routeIs($p));
        @endphp

        {{-- ── Core ── --}}
        <p class="px-2 pt-1 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Core</p>

        <a href="{{ route('hajiri.home') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/hajiri/home') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span class="flex-1 truncate">Dashboard</span>
        </a>

        @if(auth()->user()?->isAdmin())
        @php
            $canAttendance = auth()->user()?->canAccess('attendance.view');
            $canReports = auth()->user()?->canAccess(['attendance.report', 'reports.view']);
            $canLeaves = auth()->user()?->canAccess('leaves.view');
            $canUsers = auth()->user()?->canAccess('users.view');
            $canSettings = auth()->user()?->canAccess('settings.view');
            $canStaffCards = auth()->user()?->canAccess('students.card-request');
        @endphp

        {{-- ── Attendance ── --}}
        @if($canAttendance || $canReports || $canLeaves)
        <p class="px-2 pt-4 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Attendance</p>
        @endif

        @if($canAttendance)
        <a href="{{ route('hajiri.holidays.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/hajiri/holidays*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="flex-1 truncate">Holidays & Calendar</span>
        </a>
        @endif

        @if($canReports)
        <a href="{{ route('hajiri.report.modal') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/hajiri/reporting*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="flex-1 truncate">Reports</span>
        </a>
        @endif

        @if($canLeaves && \App\Services\ModuleService::enabled('hajiri_leave'))
        <a href="{{ route('hajiri.leave-requests.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/hajiri/leave-requests*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="flex-1 truncate">Leave Requests</span>
            @php $pendingCount = \App\Models\Hajiri\LeaveRequest::where('status','pending')->count(); @endphp
            @if($pendingCount > 0)
                <span class="shrink-0 min-w-4 h-4 px-1 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">{{ $pendingCount }}</span>
            @endif
        </a>
        @endif

        {{-- ── Employees ── --}}
        @if($canUsers)
        <p class="px-2 pt-4 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Employees</p>

        <a href="{{ route('hajiri.users.customs', ['typeid' => 'adminstration']) }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/hajiri/users/type/adminstration*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <span class="flex-1 truncate">Administration</span>
        </a>

        <a href="{{ route('hajiri.users.customs', ['typeid' => 'academic']) }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/hajiri/users/type/academic*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
            </svg>
            <span class="flex-1 truncate">Academic Staff</span>
        </a>
        @endif

        {{-- ── Settings ── --}}
        @if($canSettings)
        <p class="px-2 pt-4 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Settings</p>

        @if(\App\Services\ModuleService::enabled('hajiri_leave'))
        <a href="{{ route('hajiri.leave-policies.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/hajiri/leave-policies*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="flex-1 truncate">Leave Policies</span>
        </a>
        @endif

<a href="{{ route('hajiri.device.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/hajiri/devices*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2v-4M9 21H5a2 2 0 01-2-2v-4m0 0h18"/>
            </svg>
            <span class="flex-1 truncate">Devices</span>
        </a>
        @endif

        @if(auth()->user()?->isSuperAdmin())
        <a href="{{ route('hajiri.calendar-settings.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/hajiri/calendar-settings*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="flex-1 truncate">Calendar Array</span>
            <svg class="w-3 h-3 text-white/20 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
        </a>
        @endif

        {{-- ── Services ── --}}
        @if($canStaffCards)
        <p class="px-2 pt-4 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Services</p>

        <a href="{{ route('hajiri.staff-card-request.admin') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/hajiri/staff-card-requests*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2"/>
            </svg>
            <span class="flex-1 truncate">Staff ID Cards</span>
            @php $pendingCards = \App\Models\Hajiri\StaffCardRequest::where('status','pending')->count(); @endphp
            @if($pendingCards > 0)
                <span class="shrink-0 min-w-4 h-4 px-1 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">{{ $pendingCards }}</span>
            @endif
        </a>
        @endif

        @else

        {{-- ── My Attendance (Employee view) ── --}}
        <p class="px-2 pt-4 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">My Attendance</p>

        @if(\App\Services\ModuleService::enabled('hajiri_leave'))
        <a href="{{ route('hajiri.my-leaves') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/hajiri/my-leaves*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="flex-1 truncate">My Leaves</span>
        </a>
        @endif

        <a href="{{ route('hajiri.report.modal') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/hajiri/reporting*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="flex-1 truncate">My Reports</span>
        </a>

        {{-- ── Services ── --}}
        <p class="px-2 pt-4 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Services</p>

        <a href="{{ route('hajiri.staff-card-request.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/hajiri/staff-card-request*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2"/>
            </svg>
            <span class="flex-1 truncate">Request ID Card</span>
        </a>

        @endif

    </nav>

    {{-- User Footer --}}
    <div class="px-3 py-3 border-t shrink-0 sidebar-footer" style="border-color: rgba(255,255,255,0.08); background: rgba(0,0,0,0.3);">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center text-white text-xs font-bold border-2"
                 style="background-color: var(--theme-primary, #1a5632); border-color: var(--theme-secondary, #e2a024);">
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-white truncate leading-tight">{{ auth()->user()->name ?? 'User' }}</p>
                <p class="text-[10px] text-white/35 truncate leading-tight mt-0.5">{{ auth()->user()->role_label ?? 'Employee' }}</p>
            </div>
            <a href="{{ route('account.password.edit') }}" title="Change Password"
               class="shrink-0 rounded-md p-1.5 text-white/30 transition-colors hover:bg-white/10 hover:text-white">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <button type="submit" class="p-1.5 text-white/30 hover:text-white hover:bg-white/10 rounded-md transition-colors" title="Logout">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>
