@extends('layouts.admin')
@section('title', 'Manage Homepage Popups')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Homepage Popups</h2>
        <p class="text-sm text-gray-500 mt-1">Upload flyers or notices. Max 3 can be active at the same time. Newest popups show first.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm font-bold">✓ {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-xl p-4 text-sm font-bold">⚠ {{ $errors->first() }}</div>
    @endif

    <div class="grid lg:grid-cols-3 gap-8">
        
        {{-- Upload Form --}}
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-24">
                <h3 class="font-bold text-lg mb-6 text-[#0b2415] border-b pb-3">Add New Notice</h3>
                
                <form action="{{ route('admin.popups.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Notice Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" required placeholder="e.g., Admission Open 2082" class="w-full border p-3 border-gray-300 rounded-xl focus:ring-[#1a5632] focus:border-[#1a5632] text-sm">
                    </div>
                    <div>
                        <x-admin-image-picker
                            name="image_media_path"
                            file-name="image"
                            label="Flyer Image"
                            required
                            help="Choose from Media or upload a popup flyer image."
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Drive / PDF Link <span class="text-gray-400 font-normal">(Optional)</span></label>
                        <input type="url" name="link_url" placeholder="https://..." class="w-full border border-gray-300 p-3 rounded-xl focus:ring-[#1a5632] focus:border-[#1a5632] text-sm">
                    </div>
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_active" checked class="w-5 h-5 rounded text-[#1a5632] focus:ring-[#1a5632]">
                            <span class="text-sm font-bold text-gray-800">Set Active Immediately</span>
                        </label>
                    </div>
                    <button type="submit" class="w-full bg-[#1a5632] text-white font-bold py-3 rounded-xl hover:bg-[#0b2415] transition-colors shadow-md">
                        Upload & Save
                    </button>
                </form>
            </div>
        </div>

        {{-- List of Popups --}}
        <div class="lg:col-span-2 space-y-5">
            @forelse($popups as $popup)
            <div class="bg-white p-5 rounded-2xl shadow-sm border {{ $popup->is_active ? 'border-l-4 border-l-[#1a5632] border-y-gray-100 border-r-gray-100' : 'border-l-4 border-l-gray-300 border-y-gray-100 border-r-gray-100 opacity-75' }} flex flex-col sm:flex-row gap-6 items-start sm:items-center transition-all hover:shadow-md">
                
                {{-- Image Preview --}}
                <div class="shrink-0 relative">
                    <img src="{{ asset($popup->image_path) }}" class="w-32 h-32 object-cover rounded-xl border border-gray-200 shadow-sm">
                    @if($popup->is_active)
                        <span class="absolute -top-2 -right-2 bg-[#1a5632] text-white text-[10px] font-bold px-2 py-1 rounded-full shadow-md">LIVE</span>
                    @endif
                </div>
                
                {{-- Details --}}
                <div class="flex-1 min-w-0">
                    <h4 class="font-bold text-lg text-gray-900 mb-1 truncate">{{ $popup->title }}</h4>
                    <p class="text-xs text-gray-500 mb-3 truncate flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        {{ $popup->link_url ?? 'No link attached' }}
                    </p>
                    <p class="text-xs text-gray-400 mb-4 font-medium">Added: {{ $popup->created_at->format('M d, Y') }}</p>

                    {{-- Action Buttons --}}
                    <div class="flex flex-wrap items-center gap-3">
                        <form action="{{ route('admin.popups.toggle', $popup->id) }}" method="POST">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-xs font-bold px-4 py-2 rounded-lg border {{ $popup->is_active ? 'bg-green-50 border-green-200 text-green-700 hover:bg-green-100' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100' }} transition-colors">
                                {{ $popup->is_active ? 'Turn Off' : 'Turn On' }}
                            </button>
                        </form>
                        
                        <a href="{{ route('admin.popups.edit', $popup->id) }}" class="text-xs font-bold px-4 py-2 rounded-lg border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                            Edit
                        </a>

                        <form action="{{ route('admin.popups.destroy', $popup->id) }}" method="POST" onsubmit="return confirm('Permanently delete this popup?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs font-bold px-4 py-2 rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
                <div class="text-center py-16 bg-white border border-gray-100 rounded-2xl shadow-sm">
                    <p class="text-gray-500 font-medium">No popups created yet. Add your first one on the left!</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
