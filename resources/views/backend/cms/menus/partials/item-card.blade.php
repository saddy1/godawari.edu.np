@php
    $indent = match ($level ?? 0) {
        1 => 'ml-5 border-l-4 border-green-100',
        2 => 'ml-10 border-l-4 border-amber-100',
        default => '',
    };
    $badge = match ($level ?? 0) {
        1 => 'Dropdown item',
        2 => 'Nested item',
        default => 'Main nav item',
    };
@endphp

<details class="{{ $indent }} overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
    <summary class="cursor-pointer list-none">
        <div class="grid items-center gap-3 px-4 py-3 lg:grid-cols-[minmax(12rem,1fr)_auto_auto]">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="truncate text-sm font-extrabold text-gray-900">{{ $item->label }}</p>
                    @if($item->label_ne)
                        <p class="truncate text-sm font-extrabold text-gray-500">{{ $item->label_ne }}</p>
                    @endif
                    <span class="rounded-full bg-white px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-widest text-gray-400 ring-1 ring-gray-200">{{ $badge }}</span>
                    @if($item->is_active)
                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold text-emerald-700">Visible</span>
                    @else
                        <span class="rounded-full bg-gray-200 px-2 py-0.5 text-[10px] font-extrabold text-gray-500">Hidden</span>
                    @endif
                </div>
            </div>
            <p class="hidden text-xs font-semibold text-gray-400 lg:block">
                {{ $item->parent?->label ? 'Under '.$item->parent->label : 'Top level' }} · Order {{ $item->sort_order }} · {{ $item->type === 'page' ? 'CMS Page' : ($item->url ?: '#') }}
            </p>
            <span class="justify-self-end rounded-lg bg-white px-3 py-1.5 text-xs font-extrabold text-[#1a5632] ring-1 ring-gray-200">Edit</span>
        </div>
    </summary>

    <form method="POST" action="{{ route('admin.cms.menus.items.update', $item) }}" class="border-t border-gray-200 bg-white p-3">
        @csrf @method('PUT')
        @include('backend.cms.menus.partials.item-fields', ['item' => $item, 'menu' => $menu, 'pages' => $pages])
        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
            <label class="inline-flex items-center gap-2 text-sm font-bold text-gray-600">
                <input type="checkbox" name="is_active" value="1" @checked($item->is_active)> Show in menu
            </label>
            <div class="flex items-center gap-2">
                <button class="rounded-lg bg-[#1a5632] px-3 py-1.5 text-xs font-extrabold text-white">Save Item</button>
            </div>
        </div>
    </form>
    <form method="POST" action="{{ route('admin.cms.menus.items.destroy', $item) }}" onsubmit="return confirm('Delete this menu item?')" class="border-t border-gray-100 bg-white px-3 pb-3">
        @csrf @method('DELETE')
        <button class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-extrabold text-red-600 hover:bg-red-50">Delete Item</button>
    </form>
</details>
