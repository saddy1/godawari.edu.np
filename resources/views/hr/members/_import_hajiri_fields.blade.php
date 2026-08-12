{{-- Hajiri fields: shown when importing teachers/staff --}}
@if(count($hajiriOptions['designations']) || count($hajiriOptions['employmentTypes']))
<div class="rounded-xl border border-gray-100 bg-gray-50 p-4 space-y-3">
    <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Hajiri Fields (Teachers / Staff)</p>
    <div class="grid gap-3 sm:grid-cols-2">
        @if(count($hajiriOptions['designations']))
        <div><label class="{{ $label }}">Designation</label>
            <select name="designation_id" class="{{ $input }}">
                <option value="">-- None --</option>
                @foreach($hajiriOptions['designations'] as $d)<option value="{{ $d->id }}">{{ $d->label }}</option>@endforeach
            </select></div>
        @endif
        @if(count($hajiriOptions['employmentTypes']))
        <div><label class="{{ $label }}">Employment Type</label>
            <select name="employment_type_id" class="{{ $input }}">
                <option value="">-- None --</option>
                @foreach($hajiriOptions['employmentTypes'] as $e)<option value="{{ $e->id }}">{{ $e->label }}</option>@endforeach
            </select></div>
        @endif
        @if(count($hajiriOptions['workAssigned']))
        <div><label class="{{ $label }}">Work Assigned</label>
            <select name="work_assigned_id" class="{{ $input }}">
                <option value="">-- None --</option>
                @foreach($hajiriOptions['workAssigned'] as $w)<option value="{{ $w->id }}">{{ $w->label }}</option>@endforeach
            </select></div>
        @endif
        @if(count($hajiriOptions['departments']))
        <div><label class="{{ $label }}">Hajiri Department</label>
            <select name="hajiri_department_id" class="{{ $input }}">
                <option value="">-- None --</option>
                @foreach($hajiriOptions['departments'] as $dept)<option value="{{ $dept->id }}">{{ $dept->label }}</option>@endforeach
            </select></div>
        @endif
    </div>
</div>
@endif
