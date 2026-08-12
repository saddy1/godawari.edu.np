@php
    $navUser = auth()->user();
    $isScopedTeacher = $navUser?->isTeacher() && ! $navUser?->isSuperAdmin() && ! $navUser?->isPrincipal() && ! $navUser?->hasRole('administrator');
@endphp
<div class="flex flex-wrap gap-2">
    @if(auth()->user()?->canAccess('learning.courses.view'))
        <a href="{{ route('admin.learning.dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-bold border {{ request()->routeIs('admin.learning.dashboard') ? 'bg-[#1a5632] text-white border-[#1a5632]' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">Dashboard</a>
        @unless($isScopedTeacher)
        <a href="{{ route('admin.learning.classes.index') }}" class="px-3 py-2 rounded-lg text-sm font-bold border {{ request()->routeIs('admin.learning.classes.*') ? 'bg-[#1a5632] text-white border-[#1a5632]' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">Classes</a>
        <a href="{{ route('admin.learning.subjects.index') }}" class="px-3 py-2 rounded-lg text-sm font-bold border {{ request()->routeIs('admin.learning.subjects.*') ? 'bg-[#1a5632] text-white border-[#1a5632]' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">Subjects</a>
        @endunless
        <a href="{{ route('admin.learning.courses.index') }}" class="px-3 py-2 rounded-lg text-sm font-bold border {{ request()->routeIs('admin.learning.courses.*') ? 'bg-[#1a5632] text-white border-[#1a5632]' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">Courses</a>
    @endif
    @if(auth()->user()?->canAccess('learning.resources.view'))
        <a href="{{ route('admin.learning.resources.index') }}" class="px-3 py-2 rounded-lg text-sm font-bold border {{ request()->routeIs('admin.learning.resources.*') ? 'bg-[#1a5632] text-white border-[#1a5632]' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">Resources</a>
    @endif
    @if(auth()->user()?->canAccess('learning.teacher.assign'))
        <a href="{{ route('admin.learning.teacher-maps.index') }}" class="px-3 py-2 rounded-lg text-sm font-bold border {{ request()->routeIs('admin.learning.teacher-maps.*') ? 'bg-[#1a5632] text-white border-[#1a5632]' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">Teacher Mapping</a>
    @endif
    @if(auth()->user()?->canAccess('learning.students.view'))
        <a href="{{ route('admin.learning.students.index') }}" class="px-3 py-2 rounded-lg text-sm font-bold border {{ request()->routeIs('admin.learning.students.*') ? 'bg-[#1a5632] text-white border-[#1a5632]' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">Student Accounts</a>
    @endif
</div>
