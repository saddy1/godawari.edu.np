{{-- resources/views/backend/partials/sidebar.blade.php --}}

{{-- Mobile Sidebar Backdrop --}}
<div x-show="sidebarOpen" 
     x-transition.opacity 
     @click="sidebarOpen = false"
     class="fixed inset-0 z-40 bg-gray-900/80 backdrop-blur-sm lg:hidden" 
     style="display: none;"></div>

{{-- Sidebar Container --}}
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 w-72 bg-[#0b2415] text-white transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-auto flex flex-col h-screen border-r border-[#1a5632]">
    
    {{-- Sidebar Header / Logo --}}
    <div class="flex items-center justify-between h-20 px-6 bg-[#081a0f] border-b border-[#1a5632]">
        <a href="{{ url('/admin/dashboard') }}" class="flex items-center gap-3">
            {{-- Replace with actual admin mini-logo if available --}}
            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center p-1">
                <img src="{{ $siteSettings->logoUrl() }}" alt="Logo" class="w-full h-full object-contain">
            </div>
            <div>
                <span class="block text-lg font-bold text-white leading-none">{{ $siteSettings->get('app_name', 'School ERP') }}</span>
                <span class="block text-[10px] text-[#e2a024] uppercase tracking-widest mt-1">Control Panel</span>
            </div>
        </a>
        <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    {{-- Navigation Links --}}
    <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1 custom-scrollbar">
        
        <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-4">Core</p>
        
        <a href="{{ url('/admin/dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors {{ request()->is('admin/dashboard') ? 'bg-[#1a5632] text-white shadow-md' : 'text-gray-300 hover:bg-[#1a5632]/50 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            <span class="font-medium text-sm">Dashboard</span>
        </a>

        <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-6">Modules</p>
        
        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors text-gray-300 hover:bg-[#1a5632]/50 hover:text-white">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            <span class="font-medium text-sm">Academics & Classes</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors text-gray-300 hover:bg-[#1a5632]/50 hover:text-white flex justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span class="font-medium text-sm">Admissions</span>
            </div>
            <span class="bg-[#e2a024] text-[#0b2415] text-[10px] font-bold px-2 py-0.5 rounded-full">New</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors text-gray-300 hover:bg-[#1a5632]/50 hover:text-white">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
            <span class="font-medium text-sm">Notices & Events</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors text-gray-300 hover:bg-[#1a5632]/50 hover:text-white">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <span class="font-medium text-sm">Gallery Management</span>
        </a>

        <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 mt-6">Marketing & Setup</p>

        {{-- SEO Module Link --}}
        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors text-[#e2a024] bg-[#e2a024]/10 border border-[#e2a024]/20 hover:bg-[#e2a024]/20">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
            <span class="font-medium text-sm">Dynamic SEO Settings</span>
        </a>

        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors text-gray-300 hover:bg-[#1a5632]/50 hover:text-white">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <span class="font-medium text-sm">Site Configuration</span>
        </a>
    </div>

    {{-- User Footer --}}
    <div class="p-4 border-t border-[#1a5632] bg-[#081a0f]">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-[#1a5632] flex items-center justify-center text-white font-bold border-2 border-[#e2a024]">
                A
            </div>
            <div>
                <p class="text-sm font-bold text-white">{{ auth()->user()->name ?? 'Super Admin' }}</p>
                <p class="text-[10px] text-gray-400">{{ auth()->user()->email ?? $siteSettings->get('school_email', 'admin@school.edu.np') }}</p>
            </div>
        </div>
    </div>
</aside>

<style>
    /* Thin scrollbar for sidebar */
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #1a5632; border-radius: 4px; }
</style>
