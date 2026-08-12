{{-- resources/views/backend/dashboard.blade.php --}}
@extends('layouts.admin')

@section('title', 'Dashboard')
@section('header_title', 'Command Center')

@section('content')
    
    {{-- Welcome Card --}}
    <div class="bg-[#1a5632] rounded-2xl p-5 sm:p-8 text-white shadow-lg mb-6 sm:mb-8 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-[#e2a024] rounded-full blur-3xl opacity-20 -translate-y-1/2 translate-x-1/4"></div>
        <div class="relative z-10">
            <h2 class="text-2xl sm:text-3xl font-bold mb-2 leading-tight">Welcome back, {{ auth()->user()->name ?? 'Admin' }}! 👋</h2>
            <p class="text-green-100">Here is your complete overview of {{ $siteSettings->localized('site_name', config('app.name')) }}'s activities.</p>
        </div>
    </div>

    {{-- Quick Stats Grid (4 Columns) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 sm:gap-6 mb-6 sm:mb-8">
        
        {{-- Admissions Stat --}}
        <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-14 h-14 {{ $stats['new_admissions'] > 0 ? 'bg-green-50 text-[#1a5632]' : 'bg-gray-50 text-gray-400' }} rounded-xl flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pending Admissions</p>
                <p class="text-2xl font-black text-gray-900">{{ $stats['new_admissions'] }}</p>
            </div>
        </div>

        @if(auth()->user()?->canAccess(['work-tasks.view', 'work-tasks.submit', 'work-tasks.review']) && \App\Services\ModuleService::enabled('work_tasks'))
        @php $workTaskCardLabel = auth()->user()?->canAccess(['work-tasks.review', 'work-tasks.create']) ? 'Work Reviews' : 'Assigned Tasks'; @endphp
        <a href="{{ route('admin.work-tasks.index') }}" class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-14 h-14 {{ $stats['work_reviews'] > 0 ? 'bg-amber-50 text-amber-600' : 'bg-gray-50 text-gray-400' }} rounded-xl flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6m-7 4h8m-8 4h5m-6 8h10a2 2 0 002-2V7.5L15.5 4H7a2 2 0 00-2 2v13a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ $workTaskCardLabel }}</p>
                <p class="text-2xl font-black text-gray-900">{{ $stats['work_reviews'] }}</p>
            </div>
        </a>
        @endif

        {{-- Contact Messages Stat --}}
        <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-14 h-14 {{ $stats['unread_messages'] > 0 ? 'bg-red-50 text-red-600' : 'bg-gray-50 text-gray-400' }} rounded-xl flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Unread Messages</p>
                <p class="text-2xl font-black text-gray-900">{{ $stats['unread_messages'] }}</p>
            </div>
        </div>

        {{-- Events Stat --}}
        <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-14 h-14 bg-purple-50 rounded-xl flex items-center justify-center text-purple-600">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Upcoming Events</p>
                <p class="text-2xl font-black text-gray-900">{{ $stats['upcoming_events'] }}</p>
            </div>
        </div>

        {{-- Notices Stat --}}
        <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-gray-100 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-14 h-14 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Active Notices</p>
                <p class="text-2xl font-black text-gray-900">{{ $stats['active_notices'] }}</p>
            </div>
        </div>

        {{-- Library Stat --}}
        @if($libraryStats !== null)
        <a href="{{ route('admin.library.fines.index') }}"
           class="{{ $libraryStats['overdue'] > 0 ? 'bg-red-50 border-red-200' : 'bg-white border-gray-100' }} rounded-2xl p-5 sm:p-6 shadow-sm border flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="w-14 h-14 {{ $libraryStats['overdue'] > 0 ? 'bg-red-100 text-red-600' : 'bg-teal-50 text-teal-600' }} rounded-xl flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold {{ $libraryStats['overdue'] > 0 ? 'text-red-500' : 'text-gray-400' }} uppercase tracking-wider">
                    Library {{ $libraryStats['overdue'] > 0 ? '— Overdue' : '' }}
                </p>
                <p class="text-2xl font-black {{ $libraryStats['overdue'] > 0 ? 'text-red-700' : 'text-gray-900' }}">
                    {{ $libraryStats['overdue'] > 0 ? $libraryStats['overdue'].' overdue' : $libraryStats['issued'].' issued' }}
                </p>
                @if($libraryStats['fine_due'] > 0)
                    <p class="text-xs font-bold text-red-500 mt-0.5">Rs. {{ number_format($libraryStats['fine_due'], 2) }} fine pending</p>
                @endif
            </div>
        </a>
        @endif
    </div>

    {{-- MAIN 2x2 GRID FOR DATA PANELS --}}
    <div class="grid lg:grid-cols-2 gap-4 sm:gap-8">
        
        {{-- 1. ADMISSION QUERIES PANEL --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col h-[400px]">
            <div class="p-4 sm:p-5 border-b border-gray-100 flex justify-between items-center gap-3 bg-gray-50/50 shrink-0">
                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <span class="text-xl">🎓</span> Recent Admission Applications
                </h3>
                <a href="#" class="text-xs font-bold text-[#1a5632] hover:text-[#e2a024] transition-colors">View All &rarr;</a>
            </div>
            <div class="overflow-y-auto flex-1 custom-scrollbar">
                @forelse($recentAdmissions as $admission)
                <div class="p-4 border-b border-gray-50 hover:bg-gray-50 transition-colors flex items-center justify-between gap-4">
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm">{{ $admission->student_name }}</h4>
                        <p class="text-xs font-medium text-gray-500 mt-0.5">Applied for: <span class="text-[#e2a024] font-bold">{{ $admission->applied_grade }}</span></p>
                    </div>
                    <div class="text-right">
                        <span class="inline-block px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-widest {{ $admission->status === 'Pending' ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $admission->status }}
                        </span>
                        <p class="text-[10px] text-gray-400 mt-1">{{ $admission->created_at->diffForHumans() }}</p>
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center h-full text-center p-6">
                    <span class="text-3xl mb-2">📭</span>
                    <p class="text-gray-500 text-sm font-medium">No admission applications yet.</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- 2. CONTACT MESSAGES PANEL --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col h-[400px]">
            <div class="p-4 sm:p-5 border-b border-gray-100 flex justify-between items-center gap-3 bg-gray-50/50 shrink-0">
                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <span class="text-xl">💬</span> Unread Inquiries
                </h3>
                <a href="{{ route('admin.contacts.index') }}" class="text-xs font-bold text-[#1a5632] hover:text-[#e2a024] transition-colors">Inbox &rarr;</a>
            </div>
            <div class="overflow-y-auto flex-1 custom-scrollbar">
                @forelse($recentMessages as $msg)
                <div class="p-4 border-b border-gray-50 hover:bg-gray-50 transition-colors flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 font-bold uppercase text-xs">
                        {{ substr($msg->name, 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start">
                            <h4 class="font-bold text-gray-900 text-sm truncate">{{ $msg->name }}</h4>
                            <span class="text-[10px] font-bold text-gray-400 whitespace-nowrap">{{ $msg->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-[11px] font-bold text-[#1a5632] mb-0.5 truncate">{{ $msg->subject }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $msg->message }}</p>
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center h-full text-center p-6">
                    <span class="text-3xl mb-2">✨</span>
                    <p class="text-gray-500 text-sm font-medium">You're caught up! No unread messages.</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- 3. UPCOMING EVENTS PANEL --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col h-[400px]">
            <div class="p-4 sm:p-5 border-b border-gray-100 flex justify-between items-center gap-3 bg-gray-50/50 shrink-0">
                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <span class="text-xl">📅</span> Upcoming Events
                </h3>
                <a href="{{ route('admin.announcements.index') }}" class="text-xs font-bold text-[#1a5632] hover:text-[#e2a024] transition-colors">Manage &rarr;</a>
            </div>
            <div class="overflow-y-auto flex-1 p-4 custom-scrollbar space-y-3">
                @forelse($nextEvents as $event)
                @php $date = \Carbon\Carbon::parse($event->event_date); @endphp
                <div class="flex items-center gap-4 bg-gray-50/50 rounded-xl p-3 border border-gray-100 hover:border-[#1a5632]/30 transition-colors">
                    <div class="shrink-0 w-12 h-12 bg-white border border-gray-200 rounded-lg flex flex-col justify-center items-center shadow-sm">
                        <span class="text-base font-black text-[#0b2415] leading-none">{{ $date->format('d') }}</span>
                        <span class="text-[8px] font-bold uppercase tracking-widest text-[#1a5632] mt-0.5">{{ $date->format('M') }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-gray-900 text-sm truncate">{{ $event->title }}</h4>
                        <div class="flex items-center gap-2 text-[11px] text-gray-500 mt-1 font-medium">
                            <span class="bg-gray-200/50 px-1.5 py-0.5 rounded text-gray-600">{{ $event->category }}</span>
                            <span class="truncate">{{ $event->event_location ?? 'Campus' }}</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center h-full text-center">
                    <span class="text-3xl mb-2">🗓️</span>
                    <p class="text-gray-500 text-sm font-medium">No upcoming events scheduled.</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- 4. RECENT NEWS & NOTICES PANEL --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col h-[400px]">
            <div class="p-4 sm:p-5 border-b border-gray-100 flex justify-between items-center gap-3 bg-gray-50/50 shrink-0">
                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <span class="text-xl">📢</span> Recent Postings
                </h3>
                <a href="{{ route('admin.announcements.index') }}" class="text-xs font-bold text-[#1a5632] hover:text-[#e2a024] transition-colors">Manage &rarr;</a>
            </div>
            <div class="overflow-y-auto flex-1 custom-scrollbar">
                @forelse($recentPosts as $post)
                <div class="p-4 border-b border-gray-50 hover:bg-gray-50 transition-colors flex items-center justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-widest {{ $post->type === 'notice' ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600' }}">
                                {{ $post->type }}
                            </span>
                            <span class="text-[10px] text-gray-400 font-medium">{{ $post->created_at->format('M d, Y') }}</span>
                        </div>
                        <h4 class="font-bold text-gray-900 text-sm truncate">{{ $post->title }}</h4>
                    </div>
                    <div class="shrink-0 text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center h-full text-center p-6">
                    <span class="text-3xl mb-2">📝</span>
                    <p class="text-gray-500 text-sm font-medium">No recent news or notices posted.</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>
    
    {{-- CSS for clean internal scrollbars in panels --}}
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 20px; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #d1d5db; }
    </style>

@endsection
