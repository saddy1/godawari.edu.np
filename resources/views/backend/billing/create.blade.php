@extends('layouts.admin')

@section('title', 'Create Bill')

@section('content')
<div class="max-w-7xl mx-auto"
     x-data="billingForm({
        peopleUrl: @js(route('admin.billing.people.search')),
        itemsUrl: @js(route('admin.billing.items.search')),
        csrf: @js(csrf_token())
     })">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Billing</p>
            <h1 class="mt-1 text-2xl font-extrabold text-gray-950">Create Bill</h1>
            <p class="mt-1 text-sm text-gray-500">Select an HR person when available, or type a custom name.</p>
        </div>
        <a href="{{ route('admin.billing.index') }}" class="rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-extrabold text-gray-700 hover:bg-gray-50">Back</a>
    </div>

    @if($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.billing.store') }}" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        @csrf
        <input type="hidden" name="party_source_type" x-model="party.source_type">
        <input type="hidden" name="party_source_id" x-model="party.id">

        <div class="space-y-6">
            <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Bill Type</span>
                        <select name="type" x-model="type" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                            <option value="receipt">Cash received from student/person</option>
                            <option value="payment">Cash paid to student/person</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Payment Method</span>
                        <select name="payment_method" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                            <option value="cheque">Cheque</option>
                            <option value="online">Online</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-gray-950">Person Details</h2>
                        <p class="text-sm text-gray-500">Search HR master in realtime. If no record exists, keep the typed name.</p>
                    </div>
                    <button type="button" @click="clearParty()" class="text-sm font-bold text-gray-500 hover:text-red-600">Clear</button>
                </div>

                <div class="relative">
                    <label class="block">
                        <span class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Search or Custom Name</span>
                        <input name="party_name" x-model="party.name" @input.debounce.250ms="searchPeople()" autocomplete="off" required
                               placeholder="Type student, teacher, staff, or custom name"
                               class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                    </label>
                    <div x-show="people.length" @click.outside="people = []" class="absolute z-30 mt-2 max-h-72 w-full overflow-auto rounded-xl border border-gray-200 bg-white p-2 shadow-xl" style="display:none;">
                        <template x-for="person in people" :key="person.source_type + person.id">
                            <button type="button" @click="selectPerson(person)" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-green-50">
                                <span class="block text-sm font-extrabold text-gray-950" x-text="person.label"></span>
                                <span class="block text-xs font-semibold text-gray-500" x-text="[person.identifier, person.meta, person.phone].filter(Boolean).join(' · ')"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <input name="party_identifier" x-model="party.identifier" placeholder="Roll / ID / registration no." class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold outline-none focus:border-[#1a5632]">
                    <input name="party_phone" x-model="party.phone" placeholder="Phone" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold outline-none focus:border-[#1a5632]">
                    <input name="party_email" x-model="party.email" placeholder="Email" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold outline-none focus:border-[#1a5632]">
                    <input name="party_address" x-model="party.address" placeholder="Address" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold outline-none focus:border-[#1a5632]">
                </div>
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="mb-4 grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Purpose</span>
                        <input name="purpose" required placeholder="e.g. Character certificate fee" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold outline-none focus:border-[#1a5632]">
                    </label>
                    <label class="block">
                        <span class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Reference No.</span>
                        <input name="reference_no" placeholder="Cheque / bank / note reference" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold outline-none focus:border-[#1a5632]">
                    </label>
                </div>

                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-lg font-extrabold text-gray-950">Items</h2>
                    <button type="button" @click="addItem()" class="rounded-xl bg-[#1a5632] px-4 py-2 text-sm font-extrabold text-white hover:bg-[#0b2415]">+ Add Item</button>
                </div>

                <div class="space-y-3">
                    <template x-for="(item, index) in items" :key="item.key">
                        <div class="grid gap-3 rounded-xl border border-gray-100 bg-gray-50 p-3 md:grid-cols-[1fr_7rem_8rem_8rem_auto]">
                            <div class="relative">
                                <input :name="`items[${index}][description]`" x-model="item.description" @input.debounce.250ms="searchItems(index)" required placeholder="Item description"
                                       class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold outline-none focus:border-[#1a5632]">
                                <div x-show="item.suggestions.length" @click.outside="item.suggestions = []" class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-gray-200 bg-white p-1 shadow-xl" style="display:none;">
                                    <template x-for="suggestion in item.suggestions" :key="suggestion.name">
                                        <button type="button" @click="selectItem(index, suggestion)" class="block w-full rounded-md px-3 py-2 text-left text-sm font-bold hover:bg-green-50">
                                            <span x-text="suggestion.name"></span>
                                            <span class="float-right text-gray-400" x-text="money(suggestion.rate)"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <input :name="`items[${index}][quantity]`" x-model.number="item.quantity" type="number" min="0.01" step="0.01" required class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold outline-none focus:border-[#1a5632]">
                            <input :name="`items[${index}][rate]`" x-model.number="item.rate" type="number" min="0" step="0.01" required class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm font-bold outline-none focus:border-[#1a5632]">
                            <div class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm font-black text-gray-900" x-text="money(item.quantity * item.rate)"></div>
                            <button type="button" @click="removeItem(index)" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-extrabold text-red-600 hover:bg-red-50">Remove</button>
                        </div>
                    </template>
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="sticky top-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-extrabold text-gray-950">Summary</h2>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><span class="font-semibold text-gray-500">Subtotal</span><strong x-text="money(subtotal())"></strong></div>
                    <label class="flex items-center justify-between gap-3">
                        <span class="font-semibold text-gray-500">Discount</span>
                        <input name="discount" x-model.number="discount" type="number" min="0" step="0.01" class="w-32 rounded-lg border border-gray-200 px-3 py-2 text-right font-bold outline-none focus:border-[#1a5632]">
                    </label>
                    <label class="flex items-center justify-between gap-3">
                        <span class="font-semibold text-gray-500">Tax / Extra</span>
                        <input name="tax" x-model.number="tax" type="number" min="0" step="0.01" class="w-32 rounded-lg border border-gray-200 px-3 py-2 text-right font-bold outline-none focus:border-[#1a5632]">
                    </label>
                    <div class="border-t border-gray-100 pt-3">
                        <div class="flex justify-between text-lg"><span class="font-black text-gray-950">Total</span><strong class="text-[#1a5632]" x-text="money(total())"></strong></div>
                        <p class="mt-2 text-xs font-semibold text-gray-500" x-text="type === 'payment' ? 'Cash paid by school.' : 'Cash received by school.'"></p>
                    </div>
                </div>
                <textarea name="notes" rows="4" placeholder="Notes printed on bill (optional)" class="mt-5 w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold outline-none focus:border-[#1a5632]"></textarea>
                <button type="submit" class="mt-4 w-full rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-extrabold text-white shadow-sm hover:bg-[#0b2415]">Generate Bill</button>
            </section>
        </aside>
    </form>
</div>
@endsection

@push('scripts')
<script>
function billingForm(config) {
    return {
        type: 'receipt',
        discount: 0,
        tax: 0,
        people: [],
        party: { id: '', source_type: '', name: '', identifier: '', phone: '', email: '', address: '' },
        items: [{ key: Date.now(), description: 'Character Certificate', quantity: 1, rate: 300, suggestions: [] }],
        async searchPeople() {
            this.party.id = '';
            this.party.source_type = '';
            if (!this.party.name || this.party.name.length < 2) {
                this.people = [];
                return;
            }
            const response = await fetch(`${config.peopleUrl}?q=${encodeURIComponent(this.party.name)}`, { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            this.people = data.results || [];
        },
        selectPerson(person) {
            this.party = {
                id: person.id,
                source_type: person.source_type,
                name: person.name,
                identifier: person.identifier || '',
                phone: person.phone || '',
                email: person.email || '',
                address: person.address || '',
            };
            this.people = [];
        },
        clearParty() {
            this.party = { id: '', source_type: '', name: '', identifier: '', phone: '', email: '', address: '' };
            this.people = [];
        },
        addItem() {
            this.items.push({ key: Date.now() + Math.random(), description: '', quantity: 1, rate: 0, suggestions: [] });
        },
        removeItem(index) {
            if (this.items.length === 1) return;
            this.items.splice(index, 1);
        },
        async searchItems(index) {
            const item = this.items[index];
            if (!item.description || item.description.length < 2) {
                item.suggestions = [];
                return;
            }
            const response = await fetch(`${config.itemsUrl}?q=${encodeURIComponent(item.description)}`, { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            item.suggestions = data.results || [];
        },
        selectItem(index, suggestion) {
            this.items[index].description = suggestion.name;
            this.items[index].rate = suggestion.rate;
            this.items[index].suggestions = [];
        },
        subtotal() {
            return this.items.reduce((sum, item) => sum + (Number(item.quantity || 0) * Number(item.rate || 0)), 0);
        },
        total() {
            return Math.max(this.subtotal() - Number(this.discount || 0) + Number(this.tax || 0), 0);
        },
        money(value) {
            return `Rs. ${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        },
    };
}
</script>
@endpush
