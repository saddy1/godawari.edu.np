@foreach($items as $item)
    @php
        $path = trim((string) parse_url($item->resolved_url, PHP_URL_PATH), '/');
        $isActive = $item->resolved_url !== '#' && ($path === '' ? request()->is('/') : (request()->is($path) || request()->is($path.'/*')));
        $hasActiveChild = $item->children->contains(function ($child) {
            $childPath = trim((string) parse_url($child->resolved_url, PHP_URL_PATH), '/');
            return $child->resolved_url !== '#' && ($childPath === '' ? request()->is('/') : (request()->is($childPath) || request()->is($childPath.'/*')));
        });
    @endphp
    <div class="relative group/cms">
        <a href="{{ $item->resolved_url }}" target="{{ $item->target }}"
           class="inline-flex items-center gap-1 text-[14px] transition-all duration-200 {{ $isActive || $hasActiveChild ? 'text-[#1a5632] font-bold underline' : 'text-gray-700 font-medium hover:text-[#1a5632] hover:font-bold' }}">
            {{ $item->localizedLabel() }}
            @if($item->children->isNotEmpty())
                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            @endif
        </a>
        @if($item->children->isNotEmpty())
            <div class="invisible absolute left-0 top-full w-56 pt-2 opacity-0 transition-all group-hover/cms:visible group-hover/cms:opacity-100" style="z-index: 9999;">
                <div class="school-dropdown-panel overflow-hidden rounded-xl border border-gray-100 bg-white p-2 shadow-xl">
                    @include('partials.cms-menu-dropdown', ['items' => $item->children])
                </div>
            </div>
        @endif
    </div>
@endforeach
