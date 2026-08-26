@php
    $salaryUser = auth()->user();
    $salaryAdmin = $salaryUser?->isAdmin() ?? false;
    $canViewSalary = $salaryAdmin && $salaryUser?->canAccess('billing.view');
    $canCreateSalary = $salaryAdmin && $salaryUser?->canAccess('billing.create');
    $salaryHome = $canViewSalary ? route('admin.billing.payroll.index') : route('payroll.mine');
    $salaryActive = fn(string ...$patterns) => collect($patterns)->contains(fn($pattern) => request()->routeIs($pattern) || request()->is($pattern));
@endphp

<div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
     class="fixed inset-0 z-40 bg-gray-900/70 backdrop-blur-sm lg:hidden" style="display:none;"></div>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       @click.capture="if ($event.target.closest('a')) sidebarOpen = false"
       class="fixed inset-y-0 left-0 z-50 flex h-dvh w-60 shrink-0 flex-col border-r text-white transition-transform duration-300 ease-in-out lg:static lg:inset-auto lg:translate-x-0"
       style="background:linear-gradient(180deg,var(--theme-sidebar-bg,#0b2415) 0%,var(--theme-sidebar-gradient-end,#050f09) 100%);border-color:rgba(255,255,255,.08)">
    <div class="flex h-14 shrink-0 items-center justify-between border-b px-4" style="border-color:rgba(255,255,255,.08);background:rgba(0,0,0,.25)">
        <a href="{{ $salaryHome }}" class="flex min-w-0 items-center gap-2.5">
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/90 p-1"><img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="h-full w-full object-contain"></div>
            <div class="min-w-0"><p class="truncate text-sm font-bold leading-none text-white">{{ $siteSettings->get('app_name', 'School ERP') }}</p><p class="mt-0.5 text-[9px] font-semibold uppercase tracking-widest" style="color:var(--theme-secondary,#e2a024)">Salary / Pay Slip</p></div>
        </a>
        <button type="button" @click="sidebarOpen = false" class="rounded-md p-1 text-white/40 hover:bg-white/10 hover:text-white lg:hidden"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>

    <nav class="custom-scrollbar flex-1 space-y-0.5 overflow-y-auto px-2 py-3">
        <p class="px-2 pb-1.5 pt-1 text-[10px] font-bold uppercase tracking-widest text-white/30">Salary</p>

        @if($canViewSalary)
        <a href="{{ route('admin.billing.payroll.index') }}" class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium transition-all {{ $salaryActive('admin.billing.payroll.index', 'admin.billing.payroll.batch', 'admin.billing.payroll.show') ? 'bg-white/15 text-white' : 'text-white/60 hover:bg-white/8 hover:text-white' }}">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4m4-5V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V7z"/></svg><span class="flex-1 truncate">Monthly Payroll</span>
        </a>
        @endif

        @if($canCreateSalary)
        <a href="{{ route('admin.billing.payroll.template') }}" class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-white/60 transition-all hover:bg-white/8 hover:text-white">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg><span class="flex-1 truncate">Excel Template</span>
        </a>
        <a href="{{ route('admin.billing.payroll.upload') }}" class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium transition-all {{ $salaryActive('admin.billing.payroll.upload', 'admin.billing.payroll.preview') ? 'bg-white/15 text-white' : 'text-white/60 hover:bg-white/8 hover:text-white' }}">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5.002 5.002 0 0115.9 6L16 6a5 5 0 011 9.9M12 12v9m0-9l-3 3m3-3l3 3"/></svg><span class="flex-1 truncate">Upload / Re-upload</span>
        </a>
        @endif

        @if($salaryUser?->device_id)
        <a href="{{ route('payroll.mine') }}" class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium transition-all {{ $salaryActive('payroll.mine', 'payroll.mine.show') ? 'bg-white/15 text-white' : 'text-white/60 hover:bg-white/8 hover:text-white' }}">
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span class="flex-1 truncate">My Payslips</span>
        </a>
        @endif

        @if($canViewSalary || $canCreateSalary)
        <p class="px-2 pb-1.5 pt-4 text-[10px] font-bold uppercase tracking-widest text-white/30">Receipts & Payments</p>
        @if($canViewSalary)
        <a href="{{ route('admin.billing.index') }}" class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium transition-all {{ $salaryActive('admin.billing.index', 'admin.billing.show') ? 'bg-white/15 text-white' : 'text-white/60 hover:bg-white/8 hover:text-white' }}"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19z"/></svg><span class="flex-1 truncate">Bills & Receipts</span></a>
        @endif
        @if($canCreateSalary)
        <a href="{{ route('admin.billing.create') }}" class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium transition-all {{ $salaryActive('admin.billing.create') ? 'bg-white/15 text-white' : 'text-white/60 hover:bg-white/8 hover:text-white' }}"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg><span class="flex-1 truncate">New Bill</span></a>
        @endif
        @endif

        <p class="px-2 pb-1.5 pt-4 text-[10px] font-bold uppercase tracking-widest text-white/30">ERP</p>
        @if($salaryAdmin && $salaryUser?->canAccess(['dashboard.admin', 'dashboard.view', 'dashboard.financial']))
        <a href="{{ route('admin.dashboard') }}" class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-white/60 transition-all hover:bg-white/8 hover:text-white"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-2 2v8H7v-8"/></svg><span class="flex-1 truncate">Main Dashboard</span></a>
        @elseif($salaryUser?->device_id)
        <a href="{{ route('hajiri.home') }}" class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm font-medium text-white/60 transition-all hover:bg-white/8 hover:text-white"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-2 2v8H7v-8"/></svg><span class="flex-1 truncate">My Dashboard</span></a>
        @endif
    </nav>

    @include('backend.partials.sidebar-user-footer')
</aside>
