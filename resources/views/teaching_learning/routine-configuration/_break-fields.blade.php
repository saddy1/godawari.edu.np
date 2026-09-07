@php
    $breakRows = collect($breaks ?? [])->values()->all();
@endphp

<div class="sm:col-span-2 xl:col-span-6"
     x-data="{
        breaks: @js($breakRows),
        addBreak() { this.breaks.push({ name: this.breaks.length ? 'Lunch Break' : 'Short Break', after_period: '', minutes: this.breaks.length ? 30 : 10 }) },
        removeBreak(index) { this.breaks.splice(index, 1) }
     }">
    <div class="flex items-center justify-between gap-3">
        <div>
            <label class="{{ $label }}">Breaks</label>
            <p class="text-[9px] font-semibold text-gray-400">Add a short break, lunch break, or any other non-teaching interval.</p>
        </div>
        <button type="button" @click="addBreak()" x-show="breaks.length < 6"
                class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-[10px] font-black text-amber-700">
            + Add break
        </button>
    </div>

    <div class="mt-2 space-y-2" x-show="breaks.length" x-cloak>
        <template x-for="(breakItem, index) in breaks" :key="index">
            <div class="grid items-end gap-2 rounded-xl border border-amber-100 bg-amber-50/50 p-2 sm:grid-cols-[minmax(0,1fr)_9rem_8rem_auto]">
                <div>
                    <label class="{{ $label }}">Break name</label>
                    <input type="text" :name="`breaks[${index}][name]`" x-model="breakItem.name"
                           placeholder="Short Break" maxlength="40" required class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">After period</label>
                    <input type="number" :name="`breaks[${index}][after_period]`" x-model="breakItem.after_period"
                           min="1" max="20" placeholder="e.g. 2" required class="{{ $input }}">
                </div>
                <div>
                    <label class="{{ $label }}">Minutes</label>
                    <input type="number" :name="`breaks[${index}][minutes]`" x-model="breakItem.minutes"
                           min="1" max="120" placeholder="10" required class="{{ $input }}">
                </div>
                <button type="button" @click="removeBreak(index)" aria-label="Remove break"
                        class="h-10 rounded-lg border border-red-100 bg-white px-3 text-xs font-black text-red-500">Remove</button>
            </div>
        </template>
    </div>
</div>
