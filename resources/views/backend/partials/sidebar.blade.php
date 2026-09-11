{{-- Mobile Sidebar Backdrop --}}
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
    @php
        $sidebarUser = auth()->user();
        $sidebarHomeUrl = $sidebarUser?->canAccess(['dashboard.admin', 'dashboard.view', 'dashboard.financial'])
            ? url('/admin/dashboard')
            : ($sidebarUser?->canAccess(['learning.courses.view', 'learning.students.view', 'learning.lessons.view', 'learning.resources.view', 'learning.quizzes.view', 'learning.reports.view'])
                ? route('admin.learning.dashboard')
                : ($sidebarUser?->canAccess(['work-tasks.view', 'work-tasks.submit']) && \App\Services\ModuleService::enabled('work_tasks')
                    ? route('admin.work-tasks.index')
                    : ($sidebarUser?->device_id ? route('hajiri.home') : url('/'))));
    @endphp

    {{-- Logo / Brand --}}
    <div class="flex items-center justify-between h-14 px-4 border-b shrink-0" style="border-color: rgba(255,255,255,0.08); background: rgba(0,0,0,0.25);">
        <a href="{{ $sidebarHomeUrl }}" class="flex items-center gap-2.5 min-w-0">
            <div class="w-7 h-7 bg-white/90 rounded-lg flex items-center justify-center p-1 shrink-0">
                <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div class="min-w-0">
                <p class="text-sm font-bold text-white leading-none truncate">{{ $siteSettings->get('app_name', 'School ERP') }}</p>
                <p class="text-[9px] uppercase tracking-widest font-semibold mt-0.5" style="color: var(--theme-secondary, #e2a024);">Control Panel</p>
            </div>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden p-1 text-white/40 hover:text-white rounded-md hover:bg-white/10 transition-colors shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5 custom-scrollbar">

        {{-- ── Core ── --}}
        <p class="px-2 pt-1 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Core</p>

        @php
            $nav = function(string $label, string $icon, string $url, bool $active, bool $superOnly = false, int $badge = 0): void {
                // helper to echo nav item — not used; kept as reference only
            };
            $active = fn(string ...$patterns) => collect($patterns)->contains(fn($p) => request()->is($p) || request()->routeIs($p));
            $canDashboard = auth()->user()?->canAccess(['dashboard.admin', 'dashboard.view', 'dashboard.financial']);
            $canAdmissions = auth()->user()?->canAccess('students.admission');
            $canAnnouncements = auth()->user()?->canAccess('announcements.view');
            $canPopups = auth()->user()?->canAccess('popups.view');
            $canFaculty = auth()->user()?->canAccess('faculty.view');
            $canMedia = auth()->user()?->canAccess('media.view');
            $canTestimonials = auth()->user()?->canAccess('testimonials.view');
            $canVacancies = auth()->user()?->canAccess('vacancies.view');
            $canContacts = auth()->user()?->canAccess('contacts.view');
            $canSettings = auth()->user()?->canAccess('settings.view');
        @endphp

        {{-- Dashboard --}}
        @if($canDashboard)
        <a href="{{ url('/admin/dashboard') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/dashboard') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span class="flex-1 truncate">Dashboard</span>
        </a>
        @endif

        @if(auth()->user()?->device_id && \App\Services\ModuleService::enabled('billing'))
        <a href="{{ route('payroll.mine') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('my-payslips*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4m4-5V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V7z"/></svg>
            <span class="flex-1 truncate">My Payslips</span>
        </a>
        @endif

        @if(auth()->user()?->isAdmin() && \App\Services\ModuleService::enabled('teaching_learning'))
        <a href="{{ route('admin.teacher.workspace') }}" target="_blank" rel="noopener"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/teacher/workspace*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0121 12c0 2.5-.7 4.834-1.912 6.822M12 14l-6.16-3.422A12.083 12.083 0 003 12c0 2.5.7 4.834 1.912 6.822M12 14v7"/></svg>
            <span class="flex-1 truncate">Teacher/Staff Portal</span>
        </a>
        @endif

        {{-- ── Content ── --}}
        <p class="px-2 pt-4 pb-1.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">Content</p>

        @if($canAdmissions && \App\Services\ModuleService::enabled('admissions'))
        @php try { $pendingAdmissions = \App\Models\Admission::where('status','pending')->count(); } catch(\Throwable) { $pendingAdmissions = 0; } @endphp
        <a href="{{ route('admin.admissions.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/admin/admissions*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            <span class="flex-1 truncate">Admissions</span>
            @if($pendingAdmissions > 0)
                <span class="shrink-0 min-w-4 h-4 px-1 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">{{ $pendingAdmissions }}</span>
            @endif
        </a>
        @endif

        @if($canAnnouncements)
        <a href="{{ route('admin.announcements.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/announcements*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
            <span class="flex-1 truncate">Notices & Events</span>
        </a>
        @endif

        @if($canPopups)
        <a href="{{ route('admin.popups.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/admin/popups*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
            <span class="flex-1 truncate">Popup Notices</span>
        </a>
        @endif

        @if($canFaculty)
        <a href="{{ route('admin.faculty.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/admin/faculty*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span class="flex-1 truncate">Faculty Directory</span>
        </a>
        @endif

        @if($canMedia)
        <a href="{{ route('admin.gallery.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/gallery*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span class="flex-1 truncate">Gallery</span>
        </a>
        <a href="{{ route('admin.media-library.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/media-library*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
            <span class="flex-1 truncate">Media</span>
        </a>
        @endif

        @if($canTestimonials)
        <a href="{{ route('admin.testimonials.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/admin/testimonials*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
            <span class="flex-1 truncate">Testimonials</span>
        </a>
        @endif

        @if($canVacancies && \App\Services\ModuleService::enabled('vacancy'))
        @php try { $pendingVacancyApps = \App\Models\VacancyApplication::where('status','Pending')->count(); } catch(\Throwable) { $pendingVacancyApps = 0; } @endphp
        <a href="{{ route('admin.vacancies.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/admin/vacancies*','admin/admin/vacancy-applications*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span class="flex-1 truncate">Vacancies</span>
            @if($pendingVacancyApps > 0)
                <span class="shrink-0 min-w-4 h-4 px-1 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">{{ $pendingVacancyApps }}</span>
            @endif
        </a>
        @endif

        @if($canContacts)
        @php try { $unreadCount = \App\Models\ContactMessage::where('is_read', false)->count(); } catch(\Throwable) { $unreadCount = 0; } @endphp
        <a href="{{ route('admin.contacts.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/admin/contacts*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span class="flex-1 truncate">Contact Messages</span>
            @if($unreadCount > 0)
                <span class="shrink-0 min-w-4 h-4 px-1 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">{{ $unreadCount }}</span>
            @endif
        </a>
        @endif

        {{-- Key Persons --}}
        @if($canSettings)
        <a href="{{ route('admin.key-persons.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/key-persons*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="flex-1 truncate">Key Personnel</span>
        </a>
        @endif

        {{-- Quick Links --}}
        @if($canSettings)
        <a href="{{ route('admin.quick-links.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/quick-links*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
            <span class="flex-1 truncate">Quick Links</span>
        </a>
        @endif

        @if($canSettings && auth()->user()?->isSuperAdmin())
        <a href="{{ route('admin.cms.pages.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/admin/cms/pages*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h9l5 5v9a2 2 0 01-2 2zM14 4v5h5M8 13h8M8 17h5"/></svg>
            <span class="flex-1 truncate">CMS Pages</span>
        </a>
        <a href="{{ route('admin.cms.menus.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/admin/cms/menus*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h10"/></svg>
            <span class="flex-1 truncate">Menus</span>
        </a>
        <a href="{{ route('admin.home-banners.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/admin/home-banners*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4-4 3 3 5-6 4 7M4 5h16v14H4V5z"/></svg>
            <span class="flex-1 truncate">Home Banners</span>
        </a>
        <a href="{{ route('admin.home-content.index') }}"
           class="group flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-sm font-medium transition-all
                  {{ $active('admin/admin/home-content*') ? 'bg-white/15 text-white' : 'text-white/60 hover:text-white hover:bg-white/8' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h16M4 12h16M4 19h16M8 5v14"/></svg>
            <span class="flex-1 truncate">Homepage Content</span>
        </a>
        @endif

    </nav>

    @include('backend.partials.sidebar-user-footer')
</aside>
