@extends('teaching_learning.teacher-workspace.layout')

@section('title', 'My profile')

@section('content')
@php
    $value = fn(string $field, $fallback = '') => old($field, $member?->{$field} ?? $fallback);
    $organizationName = $user->organization?->name ?? $member?->organizationRecord?->name ?? 'Not assigned';
@endphp
<div class="mx-auto max-w-5xl">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div><p class="text-[10px] font-black uppercase tracking-[.22em] text-emerald-700">My account</p><h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl">Profile & services</h1><p class="mt-1 text-sm font-medium text-slate-500">Manage your personal contact and address details.</p></div>
        <a href="{{route('account.password.edit')}}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black text-slate-700 shadow-sm hover:border-emerald-300 hover:text-emerald-700">Change password</a>
    </div>

    @if(\App\Services\ModuleService::enabled('hajiri'))
    <div class="mb-5 grid grid-cols-3 gap-2 sm:gap-3">
        <a href="{{route('hajiri.home')}}" class="group rounded-2xl border border-slate-200 bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 sm:p-4"><span class="grid h-9 w-9 place-items-center rounded-xl bg-amber-50 text-amber-700">◷</span><b class="mt-2 block text-xs sm:text-sm">My Hajiri</b><span class="hidden text-xs text-slate-400 sm:block">Attendance history</span></a>
        @if(\App\Services\ModuleService::enabled('hajiri_leave'))<a href="{{route('hajiri.my-leaves')}}" class="group rounded-2xl border border-slate-200 bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-300 sm:p-4"><span class="grid h-9 w-9 place-items-center rounded-xl bg-purple-50 text-purple-700">▤</span><b class="mt-2 block text-xs sm:text-sm">My leaves</b><span class="hidden text-xs text-slate-400 sm:block">Apply and track</span></a>@endif
        <a href="{{route('hajiri.staff-card-request.index')}}" class="group rounded-2xl border border-slate-200 bg-white p-3 shadow-sm transition hover:-translate-y-0.5 hover:border-cyan-300 sm:p-4"><span class="grid h-9 w-9 place-items-center rounded-xl bg-cyan-50 text-cyan-700">▣</span><b class="mt-2 block text-xs sm:text-sm">ID card</b><span class="hidden text-xs text-slate-400 sm:block">Request and track</span></a>
    </div>
    @endif

    @if($errors->any())<div class="mb-4 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><p class="font-black">Please correct the highlighted details.</p><ul class="mt-1 list-inside list-disc">@foreach($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{route('account.profile.update')}}" enctype="multipart/form-data" class="space-y-5">
        @csrf @method('PATCH')
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="grid gap-5 border-b border-slate-100 p-5 sm:grid-cols-[160px_1fr] sm:p-6">
                <div class="flex items-center gap-4 sm:block">
                    <img id="profile-preview" src="{{$member?->photo_url ?? asset('images/default-avatar.svg')}}" class="h-24 w-24 rounded-2xl border-4 border-slate-50 object-cover shadow-sm sm:h-32 sm:w-32" alt="Profile photo">
                    <label class="mt-3 inline-flex cursor-pointer items-center rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-600 hover:bg-slate-50"><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="previewProfilePhoto(this)">Change photo</label>
                </div>
                <div>
                    <div class="mb-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-3"><span class="mt-0.5 text-amber-600">●</span><div><p class="text-xs font-black text-amber-900">Protected account information</p><p class="mt-0.5 text-xs leading-5 text-amber-700">Name, email, organization and device ID are controlled by administration and cannot be changed here.</p></div></div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach([['Name',$user->name],['Email',$user->email],['Organization',$organizationName],['Device ID',$user->device_id ?: 'Not assigned']] as [$label,$lockedValue])<div><label class="mb-1 block text-[10px] font-black uppercase tracking-wider text-slate-400">{{$label}} · Locked</label><div class="min-h-11 rounded-xl border border-slate-200 bg-slate-100 px-3 py-3 text-sm font-bold text-slate-500">{{$lockedValue}}</div></div>@endforeach
                    </div>
                </div>
            </div>

            <div class="p-5 sm:p-6">
                <h2 class="text-sm font-black text-slate-900">Personal details</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div><label class="mb-1.5 block text-xs font-black text-slate-600">Mobile number</label><input name="phone" value="{{old('phone',$user->phone ?? $member?->mobile)}}" inputmode="tel" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100"></div>
                    <div><label class="mb-1.5 block text-xs font-black text-slate-600">Date of birth (BS)</label><input name="dob_bs" value="{{$value('dob_bs')}}" placeholder="YYYY-MM-DD" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100"></div>
                    <div><label class="mb-1.5 block text-xs font-black text-slate-600">Gender</label><select name="gender" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100"><option value="">Select</option>@foreach(['Male','Female','Other'] as $gender)<option value="{{$gender}}" @selected($value('gender')===$gender)>{{$gender}}</option>@endforeach</select></div>
                    <div><label class="mb-1.5 block text-xs font-black text-slate-600">Blood group</label><select name="blood_group" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100"><option value="">Select</option>@foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $blood)<option value="{{$blood}}" @selected($value('blood_group')===$blood)>{{$blood}}</option>@endforeach</select></div>
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-xs font-black text-slate-600">Emergency contact name</label><input name="emergency_contact_name" value="{{$value('emergency_contact_name')}}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100"></div>
                    <div class="sm:col-span-2"><label class="mb-1.5 block text-xs font-black text-slate-600">Emergency contact phone</label><input name="emergency_contact_phone" value="{{$value('emergency_contact_phone')}}" inputmode="tel" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100"></div>
                    <div class="sm:col-span-2 lg:col-span-4"><label class="mb-1.5 block text-xs font-black text-slate-600">Current address summary</label><input name="address_en" value="{{$value('address_en')}}" placeholder="Street, locality or landmark" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100"></div>
                </div>
            </div>
        </section>

        @foreach([['Permanent address','permanent'],['Temporary address','temporary']] as [$heading,$prefix])
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><h2 class="text-sm font-black text-slate-900">{{$heading}}</h2><div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5"><div><label class="mb-1.5 block text-xs font-black text-slate-600">Province</label><input name="{{$prefix}}_province" value="{{$value($prefix.'_province')}}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100"></div><div><label class="mb-1.5 block text-xs font-black text-slate-600">District</label><input name="{{$prefix}}_district" value="{{$value($prefix.'_district')}}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100"></div><div><label class="mb-1.5 block text-xs font-black text-slate-600">Municipality</label><input name="{{$prefix}}_municipality" value="{{$value($prefix.'_municipality')}}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100"></div><div><label class="mb-1.5 block text-xs font-black text-slate-600">Ward</label><input name="{{$prefix}}_ward" value="{{$value($prefix.'_ward')}}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100"></div><div><label class="mb-1.5 block text-xs font-black text-slate-600">Tole</label><input name="{{$prefix}}_tole" value="{{$value($prefix.'_tole')}}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-emerald-600 focus:bg-white focus:ring-4 focus:ring-emerald-100"></div></div></section>
        @endforeach

        <div class="sticky bottom-20 z-10 flex justify-end rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-xl shadow-slate-900/10 backdrop-blur md:bottom-3"><button type="submit" class="w-full rounded-xl px-6 py-3 text-sm font-black text-white shadow-lg shadow-emerald-900/15 sm:w-auto" style="background:var(--teacher-primary)">Save my details</button></div>
    </form>
</div>
@endsection

@push('scripts')
<script>function previewProfilePhoto(input){const file=input.files?.[0];if(file)document.getElementById('profile-preview').src=URL.createObjectURL(file)}</script>
@endpush
