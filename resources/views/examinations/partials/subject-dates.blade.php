        @forelse($examination->subjects->groupBy('offering.subject_id') as $subjectId => $sets)
            @php
                $first = $sets->first();
                $subject = $first->offering->subject;
                $datesDiffer = $sets->pluck('exam_date')->map(fn($date) => $date?->format('Y-m-d'))->unique()->count() > 1
                    || $sets->pluck('practical_exam_date')->map(fn($date) => $date?->format('Y-m-d'))->unique()->count() > 1;
            @endphp
            <details class="mb-3 rounded-xl border border-gray-200" @if($errors->has('dates.'.$subjectId.'.*')) open @endif>
                <summary class="cursor-pointer px-4 py-3">
                    <span class="text-sm font-bold">{{ $subject->name }} <span class="text-xs text-gray-400">{{ $subject->code }}</span></span>
                    <span class="ml-2 text-xs font-semibold text-emerald-700">{{ $first->exam_date?->format('Y-m-d') ?? 'Date not set' }}{{ $first->exam_date ? ' AD' : '' }}</span>
                    <span class="mt-1 block text-xs text-gray-500">{{ $sets->pluck('offering.department.name')->unique()->implode(' · ') }}</span>
                    @if($datesDiffer)<span class="mt-1 block text-xs text-amber-700">Dates differ between classes. Save here to use one shared date.</span>@endif
                </summary>
                <form method="POST" action="{{ route('admin.examinations.subject-dates.update', $examination) }}" class="border-t p-4">
                    @csrf @method('PUT')
                    <fieldset @disabled($examination->is_locked) class="grid gap-4 sm:grid-cols-2">
                        <div><label class="{{$label}}">Theory date (BS)</label><x-nepali-date-input :name="'dates['.$subjectId.'][exam_date]'" :value="old('dates.'.$subjectId.'.exam_date', $first->exam_date)" :class="$input" /></div>
                        @if($examination->practical_enabled && $subject->has_practical)
                        <div><label class="{{$label}}">Practical date (BS)</label><x-nepali-date-input :name="'dates['.$subjectId.'][practical_exam_date]'" :value="old('dates.'.$subjectId.'.practical_exam_date', $first->practical_exam_date)" :class="$input" /></div>
                        @endif
                        <button class="w-fit rounded-lg bg-[#1a5632] px-4 py-2 text-xs font-bold text-white disabled:opacity-50">Save subject dates</button>
                    </fieldset>
                </form>
            </details>
        @empty
            <p class="py-8 text-center text-sm text-gray-500">Enable subjects in Edit marks first, then set their exam dates here.</p>
        @endforelse
