@foreach($items as $item)
    @php
        $path = trim((string) parse_url($item->resolved_url, PHP_URL_PATH), '/');
        $isActive = $path === '' ? request()->is('/') : request()->is($path) || request()->is($path.'/*');
    @endphp
    <div class="relative group/drop">
        <a href="{{ $item->resolved_url }}" target="{{ $item->target }}"
           class="flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition-colors {{ $isActive ? 'bg-green-50 text-[#1a5632]' : 'text-gray-800 hover:bg-green-50 hover:text-[#1a5632]' }}">
            <span>
                <span class="block">{{ $item->localizedLabel() }}</span>
                @if($item->localizedSubtitle())
                    <span class="mt-0.5 block text-xs font-medium text-gray-400">{{ $item->localizedSubtitle() }}</span>
                @endif
            </span>
            @if($item->children->isNotEmpty())
                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            @endif
        </a>
        @if($item->children->isNotEmpty())
            <div class="invisible absolute left-full top-0 w-56 pl-2 opacity-0 transition-all group-hover/drop:visible group-hover/drop:opacity-100" style="z-index: 9999;">
                <div class="school-dropdown-panel overflow-hidden rounded-xl border border-gray-100 bg-white p-2 shadow-xl">
                    @include('partials.cms-menu-dropdown', ['items' => $item->children])
                </div>
            </div>
        @endif
    </div>
@endforeach
