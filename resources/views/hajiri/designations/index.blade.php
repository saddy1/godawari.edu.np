@extends('hajiri.layouts.app')

@section('content')

{{-- Hero --}}
<div class="bg-[#1a5632] rounded-2xl p-6 sm:p-8 text-white shadow-lg mb-6 relative overflow-hidden">
    <div class="absolute -top-10 -right-10 w-48 h-48 bg-[#e2a024] rounded-full blur-3xl opacity-20 pointer-events-none"></div>
    <div class="relative z-10">
        <p class="text-[11px] font-bold text-[#e2a024] uppercase tracking-widest mb-1">HR Settings</p>
        <h2 class="text-2xl font-extrabold">Designations</h2>
        <p class="text-green-200 text-sm mt-1">Manage employee designations</p>
    </div>
</div>

@if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 mb-4">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif
@if (Session::has('message'))
    <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl px-4 py-3 mb-4">{{ Session::get('message') }}</div>
@endif

{{-- Designation List --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-4">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">Designation List</p>
    </div>

    {{-- Mobile: card list --}}
    <div class="block sm:hidden divide-y divide-gray-100">
        @foreach($designation as $key => $designation_)
            @if($designation_['alias'] == 'DEFAULT') @continue @endif
            <div class="px-4 py-3 flex items-center gap-3">
                <div class="flex-1">
                    <span id="label_m{{ $key }}" class="font-semibold text-gray-800 text-sm">{{ $designation_['label'] }}</span>
                    <input id="input_m{{ $key }}" value="{{ $designation_['label'] }}"
                           class="hidden w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#1a5632]"
                           type="text"/>
                </div>
                <button type="button" onclick="$('#submitStatus_m{{ $key }}').submit();"
                        class="shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $designation_['status'] == 1 ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-500' }}">
                    @if($designation_['status'] == 1)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    @endif
                </button>
                <form method="post" action="{{ route('hajiri.designation.update', $designation_->id) }}" class="flex gap-1.5">
                    @csrf @method('put')
                    <input id="input_mhidden_{{ $key }}" value="{{ $designation_['label'] }}" name="name" type="hidden"/>
                    <input value="{{ $designation_['status'] ? 1 : 0 }}" name="status" type="hidden"/>
                    <button id="editBtn_m{{ $key }}" type="button"
                            onclick="$('#label_m{{ $key }}').addClass('hidden'); $('#input_m{{ $key }}').removeClass('hidden'); $('#saveBtn_m{{ $key }}').removeClass('hidden'); $(this).addClass('hidden');"
                            class="inline-flex items-center justify-center w-8 h-8 border border-gray-200 text-gray-500 rounded-lg hover:border-[#1a5632] hover:text-[#1a5632] transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button id="saveBtn_m{{ $key }}" type="submit"
                            onclick="$('#input_mhidden_{{ $key }}').val($('#input_m{{ $key }}').val());"
                            class="hidden items-center justify-center w-8 h-8 bg-[#1a5632] text-white rounded-lg hover:bg-[#0b2415] transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </form>
                <form id="submitStatus_m{{ $key }}" method="post" action="{{ route('hajiri.designation.update', $designation_->id) }}" class="hidden">
                    @csrf @method('put')
                    <input value="{{ $designation_['label'] }}" type="hidden" name="name"/>
                    <input value="{{ $designation_['status'] ? 0 : 1 }}" name="status" type="hidden"/>
                </form>
            </div>
        @endforeach
    </div>

    {{-- Desktop: table --}}
    <div class="hidden sm:block overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-100">
                    <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider w-14">#</th>
                    <th class="px-4 py-3 text-left text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">Designation</th>
                    <th class="px-4 py-3 text-center text-[10px] font-extrabold text-gray-400 uppercase tracking-wider w-24">Status</th>
                    <th class="px-4 py-3 text-right text-[10px] font-extrabold text-gray-400 uppercase tracking-wider w-44">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($designation as $key => $designation_)
                    @if($designation_['alias'] == 'DEFAULT') @continue @endif
                    <tr class="hover:bg-gray-50 transition-colors">
                        <form method="post" action="{{ route('hajiri.designation.update', $designation_->id) }}">
                            @csrf @method('put')
                            <input value="{{ $designation_['status'] ? 1 : 0 }}" name="status" type="hidden"/>
                            <td class="px-4 py-3 text-xs font-bold text-gray-400">{{ $key }}</td>
                            <td class="px-4 py-3">
                                <span id="label_{{ $key }}" class="font-semibold text-gray-800">{{ $designation_['label'] }}</span>
                                <input id="input_{{ $key }}" value="{{ $designation_['label'] }}"
                                       class="hidden w-full px-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/10"
                                       type="text" name="name" placeholder="Designation name" required/>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button type="button" onclick="$('#submitStatus_{{ $key }}').submit();"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg transition-colors {{ $designation_['status'] == 1 ? 'bg-green-100 text-green-600 hover:bg-green-200' : 'bg-red-100 text-red-500 hover:bg-red-200' }}">
                                    @if($designation_['status'] == 1)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    @endif
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button id="editBtn_{{ $key }}"
                                            onclick="$('#label_{{ $key }}').addClass('hidden'); $('#input_{{ $key }}').removeClass('hidden'); $('#btn_{{ $key }}').removeClass('hidden'); $(this).addClass('hidden');"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-200 text-gray-600 text-xs font-bold rounded-lg hover:border-[#1a5632] hover:text-[#1a5632] hover:bg-green-50 transition-colors" type="button">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit
                                    </button>
                                    <button id="btn_{{ $key }}"
                                            class="hidden px-3 py-1.5 bg-[#1a5632] text-white text-xs font-bold rounded-lg hover:bg-[#0b2415] transition-colors" type="submit">
                                        <svg class="w-3.5 h-3.5 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Save
                                    </button>
                                </div>
                            </td>
                        </form>
                        <form id="submitStatus_{{ $key }}" method="post" action="{{ route('hajiri.designation.update', $designation_->id) }}">
                            @csrf @method('put')
                            <input value="{{ $designation_['label'] }}" type="hidden" name="name"/>
                            <input value="{{ $designation_['status'] ? 0 : 1 }}" name="status" type="hidden"/>
                        </form>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Add New Designation --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-6">
    <p class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-3">Add New Designation</p>
    <form method="post" action="{{ route('hajiri.designation.store') }}" class="flex flex-col sm:flex-row gap-3">
        @csrf
        <input class="flex-1 px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:border-[#1a5632] focus:ring-2 focus:ring-[#1a5632]/10"
               type="text" name="name" placeholder="Enter new designation name" required/>
        <button class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-[#e2a024] hover:bg-godawari-gold-light text-white text-sm font-extrabold rounded-xl transition-colors shrink-0" type="submit">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Add Designation
        </button>
    </form>
</div>

@endsection
