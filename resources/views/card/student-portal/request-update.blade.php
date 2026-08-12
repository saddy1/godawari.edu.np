@extends('card.student-portal.layout')
@section('title', ($isOnboarding ?? false) ? 'Review Profile' : 'Update Profile')

@section('content')
<div class="mx-auto max-w-5xl space-y-6" x-data="studentProfileUpdate()">
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <p class="text-xs font-extrabold uppercase tracking-widest text-[#1a5632]">{{ ($isOnboarding ?? false) ? 'First login' : 'Profile correction' }}</p>
        <h1 class="mt-2 text-2xl font-extrabold text-gray-950">{{ ($isOnboarding ?? false) ? 'Review Your Profile Details' : 'Update Your Profile' }}</h1>
        <p class="mt-1 text-sm font-medium text-gray-500">
            Review the current values, correct contact or address details, and submit. Changed values require administration approval.
        </p>
    </section>

    @if($errors->any())
        <section class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">
            <p class="font-extrabold">Please correct the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 font-semibold">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </section>
    @endif

    @if($pending)
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-sm font-extrabold text-amber-950">Your update request is pending approval</p>
            <p class="mt-2 text-sm font-semibold leading-6 text-amber-800">
                Requested fields: {{ collect(array_keys($pending->requested_changes ?? []))->map(fn ($field) => str($field)->headline())->implode(', ') }}.
                Submitted {{ $pending->created_at->diffForHumans() }}.
            </p>
        </section>
    @endif

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Locked academic identity</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                'Roll Number' => $student->roll_number,
                'Organization' => $student->organization_record?->name ?? $student->organization,
                'Class / Faculty' => $student->stream,
                'Section' => $student->section,
            ] as $label => $value)
                <div class="rounded-xl bg-gray-100 px-4 py-3">
                    <p class="text-[10px] font-extrabold uppercase tracking-widest text-gray-400">{{ $label }}</p>
                    <p class="mt-1 text-sm font-extrabold text-gray-700">{{ filled($value) ? $value : 'Not set' }}</p>
                </div>
            @endforeach
        </div>
        <p class="mt-3 text-xs font-semibold text-gray-500">These fields cannot be changed from the student portal.</p>
    </section>

    <form method="POST" action="{{ route('student.submit-update') }}" enctype="multipart/form-data" class="rounded-2xl border border-gray-200 bg-white shadow-sm {{ $pending ? 'pointer-events-none opacity-60' : '' }}">
        @csrf

        <div class="border-b border-gray-100 p-5">
            <h2 class="text-base font-extrabold text-gray-950">Profile Photo</h2>
            <p class="mt-1 text-xs font-semibold text-gray-500">Take a clear passport-style photo or upload one from your device. Maximum size: 2 MB.</p>
            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="h-32 w-28 shrink-0 overflow-hidden rounded-2xl border-2 border-gray-200 bg-gray-100 shadow-sm">
                    <img :src="photoPreview" alt="Profile photo preview" class="h-full w-full object-cover">
                </div>
                <div class="flex-1">
                    <input x-ref="profilePhoto" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="hidden" @change="previewSelectedPhoto($event)">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <button type="button" @click="choosePhoto(true)" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#1a5632] px-4 py-3 text-sm font-extrabold text-white hover:bg-[#0b2415]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h1l2-3h8l2 3h1a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3" stroke-width="2"/></svg>
                            Open Rear Camera
                        </button>
                        <button type="button" @click="choosePhoto(false)" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-extrabold text-gray-700 hover:bg-gray-50">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 4v12m0-12l-4 4m4-4l4 4"/></svg>
                            Upload Image
                        </button>
                    </div>
                    <p class="mt-2 text-xs font-bold text-[#1a5632]" x-show="photoName" x-text="photoName"></p>
                    <p class="mt-2 text-xs font-medium text-gray-400">On mobile, “Open Rear Camera” requests the back camera directly.</p>
                </div>
            </div>
        </div>

        <div class="border-b border-gray-100 p-5">
            <h2 class="text-base font-extrabold text-gray-950">Date of Birth & Family Details</h2>
            <p class="mt-1 text-xs font-semibold text-gray-500">Tap the calendar button to choose the date of birth in Bikram Sambat.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Date of Birth (BS)</label>
                    <input type="text" name="dob_bs" value="{{ old('dob_bs', $student->dob_bs) }}" placeholder="YYYY-MM-DD" maxlength="10" inputmode="numeric" data-min-bs="2000-01-01" data-max-bs="today" class="nepali-date-picker w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                </div>
                @foreach([
                    'father_name' => ['Father Name', $student->father_name],
                    'mother_name' => ['Mother Name', $student->mother_name],
                    'parent_contact' => ['Parent Contact', $student->parent_contact],
                    'grandfather_name' => ['Grandfather Name', $student->grandfather_name],
                ] as $field => [$familyLabel, $current])
                    <div>
                        <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">{{ $familyLabel }}</label>
                        <input name="{{ $field }}" value="{{ old($field, $current) }}" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 p-5 lg:grid-cols-2">
            <div>
                <h2 class="text-base font-extrabold text-gray-950">Contact Details</h2>
                <div class="mt-4 space-y-4">
                    @foreach([
                        'mobile' => ['Mobile Number', $student->mobile, 'text'],
                        'email' => ['Email Address', $student->email, 'email'],
                        'guardian_contact' => ['Guardian Contact', $student->guardian_contact, 'text'],
                    ] as $field => [$label, $current, $type])
                        <div>
                            <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">{{ $label }}</label>
                            <input type="{{ $type }}" name="{{ $field }}" value="{{ old($field, $current) }}" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h2 class="text-base font-extrabold text-gray-950">Permanent Address</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Province</label>
                        <select name="permanent_province" x-model="permProvince" @change="permDistrict = ''; permMunicipality = ''; syncTemporary()" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                            <option value="">Select Province</option>
                            <template x-for="province in addressData.provinces" :key="province"><option :value="province" x-text="province"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">District</label>
                        <select name="permanent_district" x-model="permDistrict" @change="permMunicipality = ''; syncTemporary()" :disabled="!permProvince" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold disabled:bg-gray-100 focus:border-[#1a5632] focus:outline-none">
                            <option value="">Select District</option>
                            <template x-for="district in permanentDistricts" :key="district"><option :value="district" x-text="district"></option></template>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Municipality / Rural Municipality</label>
                        <select name="permanent_municipality" x-model="permMunicipality" @change="syncTemporary()" :disabled="!permDistrict" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold disabled:bg-gray-100 focus:border-[#1a5632] focus:outline-none">
                            <option value="">Select Municipality</option>
                            <template x-for="municipality in permanentMunicipalities" :key="municipality"><option :value="municipality" x-text="municipality"></option></template>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Ward</label>
                        <input name="permanent_ward" x-model="permWard" @input="syncTemporary()" inputmode="numeric" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Tole / Street</label>
                        <input name="permanent_tole" x-model="permTole" @input="syncTemporary()" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-base font-extrabold text-gray-950">Temporary Address</h2>
                <label class="inline-flex items-center gap-2 text-xs font-extrabold text-gray-600">
                    <input type="checkbox" x-model="sameAddress" @change="syncTemporary()" class="rounded border-gray-300 text-[#1a5632] focus:ring-[#1a5632]">
                    Same as permanent address
                </label>
            </div>
            <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Province</label>
                    <select name="temporary_province" x-model="tempProvince" @change="sameAddress = false; tempDistrict = ''; tempMunicipality = ''" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                        <option value="">Select Province</option>
                        <template x-for="province in addressData.provinces" :key="province"><option :value="province" x-text="province"></option></template>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">District</label>
                    <select name="temporary_district" x-model="tempDistrict" @change="sameAddress = false; tempMunicipality = ''" :disabled="!tempProvince" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-3 text-sm font-semibold disabled:bg-gray-100 focus:border-[#1a5632] focus:outline-none">
                        <option value="">Select District</option>
                        <template x-for="district in temporaryDistricts" :key="district"><option :value="district" x-text="district"></option></template>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Municipality</label>
                    <select name="temporary_municipality" x-model="tempMunicipality" @change="sameAddress = false" :disabled="!tempDistrict" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-3 text-sm font-semibold disabled:bg-gray-100 focus:border-[#1a5632] focus:outline-none">
                        <option value="">Select Municipality</option>
                        <template x-for="municipality in temporaryMunicipalities" :key="municipality"><option :value="municipality" x-text="municipality"></option></template>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Ward</label>
                    <input name="temporary_ward" x-model="tempWard" @input="sameAddress = false" inputmode="numeric" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Tole / Street</label>
                    <input name="temporary_tole" x-model="tempTole" @input="sameAddress = false" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none">
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-gray-100 bg-gray-50 p-5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs font-semibold leading-5 text-gray-500">Only values different from the approved record are sent to administration.</p>
            <button @disabled($pending) class="rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-extrabold text-white hover:bg-[#0b2415] disabled:cursor-not-allowed disabled:opacity-50">
                {{ ($isOnboarding ?? false) ? 'Confirm and Continue' : 'Submit for Approval' }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/nepal-address.js') }}"></script>
@include('partials.nepali-date-picker')
<script>
function studentProfileUpdate() {
    return {
        addressData: NEPAL_ADDR,
        permProvince: @json(old('permanent_province', $student->permanent_province)),
        permDistrict: @json(old('permanent_district', $student->permanent_district)),
        permMunicipality: @json(old('permanent_municipality', $student->permanent_municipality)),
        permWard: @json(old('permanent_ward', $student->permanent_ward)),
        permTole: @json(old('permanent_tole', $student->permanent_tole)),
        tempProvince: @json(old('temporary_province', $student->temporary_province)),
        tempDistrict: @json(old('temporary_district', $student->temporary_district)),
        tempMunicipality: @json(old('temporary_municipality', $student->temporary_municipality)),
        tempWard: @json(old('temporary_ward', $student->temporary_ward)),
        tempTole: @json(old('temporary_tole', $student->temporary_tole)),
        sameAddress: false,
        photoPreview: @json($student->photo ? $student->photo_url : asset('images/default-avatar.svg')),
        photoName: '',

        get permanentDistricts() { return this.addressData.districts[this.permProvince] || []; },
        get permanentMunicipalities() { return this.addressData.municipalities[this.permDistrict] || []; },
        get temporaryDistricts() { return this.addressData.districts[this.tempProvince] || []; },
        get temporaryMunicipalities() { return this.addressData.municipalities[this.tempDistrict] || []; },

        choosePhoto(useCamera) {
            const input = this.$refs.profilePhoto;
            input.value = '';
            if (useCamera) {
                input.setAttribute('capture', 'environment');
            } else {
                input.removeAttribute('capture');
            }
            input.click();
        },

        previewSelectedPhoto(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            this.photoPreview = URL.createObjectURL(file);
            this.photoName = file.name;
        },

        syncTemporary() {
            if (!this.sameAddress) return;
            this.tempProvince = this.permProvince;
            this.tempDistrict = this.permDistrict;
            this.tempMunicipality = this.permMunicipality;
            this.tempWard = this.permWard;
            this.tempTole = this.permTole;
        }
    };
}
</script>
@endpush
