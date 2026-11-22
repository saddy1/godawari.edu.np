{{-- Student 360 tab nav — only meaningful for students, shown on the
     profile and every activity sub-page so an admin can jump between them
     while looking into one guardian's question. --}}
@php
    $activityTabs = [
        ['route' => 'admin.hr.members.show', 'label' => 'Profile'],
        ['route' => 'admin.hr.members.activity.attendance', 'label' => 'Attendance'],
        ['route' => 'admin.hr.members.activity.exams', 'label' => 'Exams & Results'],
    ];
    if (\App\Services\ModuleService::enabled('counselling')) {
        $activityTabs[] = ['route' => 'admin.hr.members.activity.counselling', 'label' => 'Counselling'];
    }
@endphp
<div class="flex flex-wrap gap-1 rounded-2xl border border-gray-200 bg-white p-1.5 shadow-sm">
    @foreach($activityTabs as $tab)
        <a href="{{ route($tab['route'], $member) }}"
           class="rounded-xl px-4 py-2 text-xs font-extrabold transition {{ request()->routeIs($tab['route']) ? 'bg-[#1a5632] text-white' : 'text-gray-600 hover:bg-gray-50' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>
