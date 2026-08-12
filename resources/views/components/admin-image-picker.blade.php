@props([
    'name',
    'label'       => 'Image',
    'currentUrl'  => null,
    'currentPath' => null,
    'required'    => false,
    'help'        => null,
    'fileName'    => null,
    'accept'      => null,
    'mediaMode'   => 'image',
])

@php
    $pickerId = 'picker_'.str_replace(['[', ']', '.', '-'], '_', $name).'_'.substr(md5($name.$label), 0, 6);
@endphp

<div
    x-data="{
        selectedPath: @js(old($name, $currentPath)),
        previewUrl:   @js($currentUrl),
        previewIsImage: @js(! $currentPath || ! str_ends_with(strtolower($currentPath), '.pdf')),
        selectedFileName: '',
        handleSelected(detail) {
            if (!detail || !detail.request || detail.request.target !== '{{ $pickerId }}') return;
            this.selectedPath = detail.image.file_path;
            this.previewUrl   = detail.image.url;
            this.previewIsImage = (detail.image.mime_type || '').startsWith('image/');
            this.selectedFileName = '';
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },
        clear() {
            this.selectedPath = '';
            this.previewUrl   = null;
            this.previewIsImage = true;
            this.selectedFileName = '';
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },
        handleFile(event) {
            const file = event.target.files[0];
            this.selectedFileName = file ? file.name : '';

            if (file) {
                this.selectedPath = '';
                this.previewIsImage = file.type.startsWith('image/');
                this.previewUrl = this.previewIsImage ? URL.createObjectURL(file) : null;
            }
        }
    }"
    @image-selected.window="handleSelected($event.detail)"
    class="space-y-2"
>
    @if($label)
        <label class="block text-sm font-bold text-gray-700">
            {{ $label }}
            @if($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif

    <input type="hidden" name="{{ $name }}" x-model="selectedPath">

    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-3 space-y-3">

        {{-- Preview --}}
        <div class="flex h-36 w-full items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-white">
            <template x-if="previewUrl && previewIsImage">
                <img :src="previewUrl" alt="{{ $label }}" class="h-full w-full object-contain p-1">
            </template>
            <template x-if="!previewUrl || !previewIsImage">
                <div class="text-center text-xs font-semibold text-gray-400" x-text="selectedFileName || (previewIsImage ? 'No image selected' : 'Document selected')"></div>
            </template>
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-2">
            <button type="button"
                @click="window.dispatchEvent(new CustomEvent('open-media-manager', { detail: { target: '{{ $pickerId }}', mode: @js($mediaMode) } }))"
                class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Choose from Media
            </button>

            <button type="button" x-show="selectedPath || selectedFileName" x-cloak @click="clear()"
                class="rounded-xl border border-red-100 bg-red-50 px-4 py-2.5 text-sm font-bold text-red-600 hover:bg-red-100 transition-colors">
                Clear
            </button>
        </div>

        @if($fileName)
            <div>
                <label class="mb-1 block text-xs font-bold text-gray-600">Or upload from this device</label>
                <input
                    x-ref="fileInput"
                    type="file"
                    name="{{ $fileName }}"
                    @if($accept) accept="{{ $accept }}" @endif
                    @change="handleFile($event)"
                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-[#1a5632] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white"
                >
                <p x-show="selectedFileName" x-text="selectedFileName" class="mt-1 break-all text-xs font-semibold text-[#1a5632]"></p>
            </div>
        @endif

        @if($help)
            <p class="text-xs text-gray-500">{{ $help }}</p>
        @endif

        @if($errors->has($name) || ($fileName && $errors->has($fileName)))
            <p class="text-xs font-bold text-red-600">
                {{ $errors->first($name) ?: $errors->first($fileName) }}
            </p>
        @endif

    </div>
</div>
