@php
    $storeLinks = [
        ['label' => 'Overview', 'route' => 'admin.store.dashboard', 'active' => 'admin.store.dashboard'],
        ['label' => 'Suppliers', 'route' => 'admin.store.suppliers.index', 'active' => 'admin.store.suppliers.*'],
        ['label' => 'Categories', 'route' => 'admin.store.categories.index', 'active' => 'admin.store.categories.*'],
        ['label' => 'Brands', 'route' => 'admin.store.brands.index', 'active' => 'admin.store.brands.*'],
        ['label' => 'Units', 'route' => 'admin.store.units.index', 'active' => 'admin.store.units.*'],
    ];
@endphp

<aside class="rounded-2xl border border-gray-100 bg-white p-3 shadow-sm">
    <p class="px-3 py-2 text-xs font-black uppercase tracking-[0.22em] text-gray-400">Store Menu</p>
    <nav class="space-y-1">
        @foreach($storeLinks as $link)
            <a href="{{ route($link['route']) }}"
               class="block rounded-xl px-3 py-2.5 text-sm font-black transition {{ request()->routeIs($link['active']) ? 'bg-[#1a5632] text-white' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-950' }}">
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
    <div class="mt-3 border-t border-gray-100 pt-3">
        <a href="{{ route('admin.store.forms.show', ['type' => 'ledger-consumable']) }}" class="block rounded-xl px-3 py-2 text-sm font-bold text-gray-600 hover:bg-gray-50">Form 33 Ledger</a>
        <a href="{{ route('admin.store.forms.show', ['type' => 'ledger-non-consumable']) }}" class="block rounded-xl px-3 py-2 text-sm font-bold text-gray-600 hover:bg-gray-50">Form 32 Ledger</a>
    </div>
</aside>
