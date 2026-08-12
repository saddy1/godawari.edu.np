@extends('layouts.admin')

@section('title', $page->exists ? 'Edit Page' : 'Create Page')

@section('content')
@php
    $initialBlocks = old('content_blocks')
        ? json_decode(old('content_blocks'), true)
        : ($page->content_blocks ?? []);
    $initialBlocksNe = old('content_blocks_ne')
        ? json_decode(old('content_blocks_ne'), true)
        : ($page->content_blocks_ne ?? []);
@endphp
<form id="cms-page-form" method="POST" action="{{ $page->exists ? route('admin.cms.pages.update', $page) : route('admin.cms.pages.store') }}" enctype="multipart/form-data"
      x-data="cmsPageEditor(@js($initialBlocks ?: []), @js($initialBlocksNe ?: []))"
      @image-selected.window="insertSelectedMedia($event.detail)"
      class="space-y-6 pb-16">
    @csrf
    @if($page->exists) @method('PUT') @endif
    <input type="hidden" name="content_blocks" :value="JSON.stringify(blocksEn)">
    <input type="hidden" name="content_blocks_ne" :value="JSON.stringify(blocksNe)">

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-5 shadow-sm border border-gray-100">
        <div>
            <a href="{{ route('admin.cms.pages.index') }}" class="text-xs font-bold uppercase tracking-widest text-[#1a5632] hover:underline">Pages</a>
            <h1 class="mt-1 text-2xl font-extrabold text-gray-900">{{ $page->exists ? 'Edit Page' : 'Create Page' }}</h1>
        </div>
        <div class="flex gap-2">
            <button type="button" @click="settingsOpen = true" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2 text-sm font-extrabold text-gray-700 hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.607 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Settings
            </button>
            <a href="{{ route('admin.cms.pages.index') }}" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-extrabold text-gray-600 hover:bg-gray-50">Cancel</a>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="space-y-4">
            <div class="flex w-fit rounded-xl border border-gray-200 bg-white p-1 shadow-sm">
                <button type="button" @click="switchLanguage('en')" :class="activeLanguage === 'en' ? 'bg-[#1a5632] text-white' : 'text-gray-600 hover:bg-gray-50'" class="rounded-lg px-4 py-2 text-sm font-extrabold">English</button>
                <button type="button" @click="switchLanguage('ne')" :class="activeLanguage === 'ne' ? 'bg-[#1a5632] text-white' : 'text-gray-600 hover:bg-gray-50'" class="rounded-lg px-4 py-2 text-sm font-extrabold">नेपाली</button>
            </div>
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-400">Title</label>
                        <input x-show="activeLanguage === 'en'" name="title" value="{{ old('title', $page->title) }}" required class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20">
                        <input x-show="activeLanguage === 'ne'" name="title_ne" value="{{ old('title_ne', $page->title_ne) }}" placeholder="नेपाली शीर्षक" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm font-bold focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-400">Slug</label>
                        <input name="slug" value="{{ old('slug', $page->slug) }}" placeholder="auto-generated from title" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-400">Parent Page</label>
                        <select name="parent_id" class="w-full rounded-xl border border-gray-200 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#1a5632]/20">
                            <option value="">No parent</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" @selected(old('parent_id', $page->parent_id) == $parent->id)>{{ $parent->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 bg-gray-50 px-5 py-3">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Full Page Live Preview</p>
                        <p class="mt-1 text-xs font-semibold text-gray-500">Click any section or content block in the preview to edit it here.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="rounded-full bg-white px-3 py-1 text-[11px] font-extrabold uppercase tracking-widest text-gray-400" x-text="activeLanguage === 'ne' ? 'नेपाली' : 'English'"></p>
                        <button type="button" @click="togglePreviewSectionPicker(blocks.length, $event)" class="rounded-xl bg-[#1a5632] px-3 py-2 text-xs font-extrabold text-white hover:bg-[#0b2415]">Add section</button>
                    </div>
                </div>
                <div class="cms-builder-preview cms-builder-preview--full bg-slate-100 p-4" @click="handlePreviewClick($event)">
                    <div x-show="blocks.length" x-html="renderPagePreview()"></div>
                    <div x-show="blocks.length === 0" class="rounded-xl border-2 border-dashed border-gray-300 bg-white py-10 text-center text-sm font-bold text-gray-400">
                        <p>Add a section to see the page preview here.</p>
                        <button type="button" @click.stop="togglePreviewSectionPicker(0, $event)" class="mt-4 rounded-xl bg-[#1a5632] px-4 py-2 text-xs font-extrabold text-white">Add section</button>
                    </div>
                </div>
            </div>

            <div x-show="inlineEditor" x-transition.opacity class="fixed inset-0 z-[130] bg-slate-950/40 p-4 backdrop-blur-sm" style="display:none;">
                <div class="ml-auto flex h-full w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" @click.outside="closeInlineEditor()">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400" x-text="inlineEditorLabel()"></p>
                            <h2 class="text-lg font-extrabold text-gray-950">Edit from live preview</h2>
                        </div>
                        <button type="button" @click="closeInlineEditor()" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-extrabold text-gray-600 hover:bg-gray-50">Close</button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-5">
                        <template x-if="inlineEditor && inlineEditor.column === null">
                            <div class="space-y-4">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-400">Section</span>
                                        <select x-model="selectedInlineRow().data.section" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                                            <option value="normal">Normal section</option>
                                            <option value="hero">Hero section</option>
                                            <option value="stats">Stats bar</option>
                                            <option value="cards">Card grid</option>
                                            <option value="dark">Dark highlight</option>
                                            <option value="cta">Call to action</option>
                                        </select>
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-400">Columns</span>
                                        <select x-model.number="selectedInlineRow().data.columns" @change="setRowColumns(selectedInlineRow(), selectedInlineRow().data.columns)" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                                            <option value="1">1 Column</option>
                                            <option value="2">2 Columns</option>
                                            <option value="3">3 Columns</option>
                                            <option value="4">4 Columns</option>
                                        </select>
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-400">Palette</span>
                                        <select x-model="selectedInlineRow().data.palette" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                                            <option value="default">Default</option>
                                            <option value="light">Light</option>
                                            <option value="dark">Dark</option>
                                            <option value="green">Green</option>
                                            <option value="blue">Blue</option>
                                            <option value="amber">Amber</option>
                                            <option value="image">Image background</option>
                                        </select>
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-400">Pattern</span>
                                        <select x-model="selectedInlineRow().data.pattern" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                                            <option value="none">No pattern</option>
                                            <option value="grid">Grid</option>
                                            <option value="dots">Dots</option>
                                            <option value="diagonal">Diagonal</option>
                                        </select>
                                    </label>
                                </div>

                                <label class="block">
                                    <span class="mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-400">Small Label</span>
                                    <input x-model="selectedInlineRow().data.eyebrow" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-400">Title</span>
                                    <input x-model="selectedInlineRow().data.title" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-400">Description</span>
                                    <textarea x-model="selectedInlineRow().data.description" rows="4" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm leading-7"></textarea>
                                </label>

                                <div x-show="selectedInlineRow().data.section === 'hero'" class="space-y-3 rounded-2xl border border-gray-100 bg-gray-50 p-3">
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-400">Hero Badge</span>
                                        <input x-model="selectedInlineRow().data.badge" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                    </label>
                                    <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto]">
                                        <input x-model="selectedInlineRow().data.image" placeholder="Hero image URL" class="min-w-0 rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                        <button type="button" @click="openHeroMedia(inlineEditor.row)" class="rounded-xl bg-[#1a5632] px-4 py-2 text-xs font-extrabold text-white">Choose</button>
                                    </div>
                                </div>

                                <div x-show="['hero','cta'].includes(selectedInlineRow().data.section)" class="grid gap-3 sm:grid-cols-2">
                                    <input x-model="selectedInlineRow().data.primary_label" placeholder="Primary button label" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                    <input x-model="selectedInlineRow().data.primary_url" placeholder="Primary button URL" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                    <input x-model="selectedInlineRow().data.secondary_label" placeholder="Secondary button label" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                    <input x-model="selectedInlineRow().data.secondary_url" placeholder="Secondary button URL" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                </div>
                            </div>
                        </template>

                        <template x-if="inlineEditor && inlineEditor.column !== null && selectedInlineBlock()">
                            <div class="space-y-4">
                                <div class="rounded-2xl border border-gray-100 bg-gray-50 p-3">
                                    <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400" x-text="toolFor(selectedInlineBlock().type).label"></p>
                                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                        <select x-model="selectedInlineBlock().data.align" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-bold">
                                            <option value="left">Left aligned</option>
                                            <option value="center">Centered</option>
                                            <option value="right">Right aligned</option>
                                        </select>
                                        <select x-show="selectedInlineBlock().type === 'button'" x-model="selectedInlineBlock().data.style" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-bold">
                                            <option value="primary">Primary button</option>
                                            <option value="outline">Outline button</option>
                                            <option value="dark">Dark button</option>
                                        </select>
                                    </div>
                                </div>

                                <template x-if="selectedInlineBlock().type === 'heading'">
                                    <div class="grid gap-3 sm:grid-cols-[8rem_minmax(0,1fr)]">
                                        <select x-model="selectedInlineBlock().data.level" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                                            <option value="2">Heading 2</option>
                                            <option value="3">Heading 3</option>
                                            <option value="4">Heading 4</option>
                                        </select>
                                        <input x-model="selectedInlineBlock().data.text" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                                    </div>
                                </template>
                                <template x-if="selectedInlineBlock().type === 'paragraph'">
                                    <textarea x-model="selectedInlineBlock().data.text" rows="7" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm leading-7"></textarea>
                                </template>
                                <template x-if="selectedInlineBlock().type === 'image'">
                                    <div class="space-y-3">
                                        <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto]">
                                            <input x-model="selectedInlineBlock().data.url" placeholder="Image URL" class="min-w-0 rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                            <button type="button" @click="openMedia(inlineEditor.row, inlineEditor.column, inlineEditor.block, 'image')" class="rounded-xl bg-[#1a5632] px-4 py-2 text-xs font-extrabold text-white">Choose</button>
                                        </div>
                                        <input x-model="selectedInlineBlock().data.caption" placeholder="Caption" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                    </div>
                                </template>
                                <template x-if="selectedInlineBlock().type === 'feature_card'">
                                    <div class="grid gap-3">
                                        <input x-model="selectedInlineBlock().data.icon" placeholder="Icon / emoji" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                        <input x-model="selectedInlineBlock().data.title" placeholder="Card title" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                                        <textarea x-model="selectedInlineBlock().data.text" rows="5" placeholder="Card text" class="rounded-xl border border-gray-200 px-3 py-2 text-sm leading-7"></textarea>
                                    </div>
                                </template>
                                <template x-if="selectedInlineBlock().type === 'stat'">
                                    <div class="grid gap-3">
                                        <input x-model="selectedInlineBlock().data.label" placeholder="Label" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                        <input x-model="selectedInlineBlock().data.value" placeholder="Value" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                                    </div>
                                </template>
                                <template x-if="selectedInlineBlock().type === 'button'">
                                    <div class="grid gap-3">
                                        <input x-model="selectedInlineBlock().data.label" placeholder="Button label" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                        <input x-model="selectedInlineBlock().data.url" placeholder="Button URL" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                    </div>
                                </template>
                                <template x-if="selectedInlineBlock().type === 'testimonial'">
                                    <div class="grid gap-3">
                                        <textarea x-model="selectedInlineBlock().data.quote" rows="5" placeholder="Quote" class="rounded-xl border border-gray-200 px-3 py-2 text-sm leading-7"></textarea>
                                        <input x-model="selectedInlineBlock().data.name" placeholder="Name" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                                        <input x-model="selectedInlineBlock().data.role" placeholder="Role" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                    </div>
                                </template>
                                <template x-if="['video','gallery','table','html'].includes(selectedInlineBlock().type)">
                                    <div class="grid gap-3">
                                        <textarea x-show="selectedInlineBlock().type === 'gallery'" x-model="selectedInlineBlock().data.images" rows="6" placeholder="One image URL per line" class="rounded-xl border border-gray-200 px-3 py-2 text-sm"></textarea>
                                        <input x-show="selectedInlineBlock().type === 'video'" x-model="selectedInlineBlock().data.url" placeholder="Video URL" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                        <textarea x-show="selectedInlineBlock().type === 'table'" x-model="selectedInlineBlock().data.rows" rows="7" placeholder="CSV rows or table JSON" class="rounded-xl border border-gray-200 px-3 py-2 font-mono text-xs"></textarea>
                                        <textarea x-show="selectedInlineBlock().type === 'html'" x-model="selectedInlineBlock().data.html" rows="7" placeholder="Trusted HTML" class="rounded-xl border border-gray-200 bg-slate-950 px-3 py-2 font-mono text-xs text-white"></textarea>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <div class="flex flex-wrap justify-between gap-2 border-t border-gray-100 bg-gray-50 px-5 py-4">
                        <button type="button" @click="revealAdvancedEditor()" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-extrabold text-gray-600">Advanced controls</button>
                        <button type="button" @click="closeInlineEditor()" class="rounded-xl bg-[#1a5632] px-5 py-2 text-sm font-extrabold text-white">Done</button>
                    </div>
                </div>
            </div>

            <div id="cms-page-layout-editor" x-show="advancedLayoutOpen" x-transition class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm" style="display:none;">
                <div class="mb-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Page Layout</p>
                            <p class="mt-1 text-sm text-gray-500" x-text="advancedLayoutScope ? 'Advanced controls for the selected section only.' : 'First choose a row layout, then add heading, text, media, table, button, or HTML inside each column.'"></p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" x-show="advancedLayoutScope" @click="showFullLayoutEditor()" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-extrabold text-gray-600">Show all sections</button>
                            <button type="button" @click="hideLayoutEditor()" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-extrabold text-gray-600">Hide</button>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <div x-show="!advancedLayoutScope" class="group relative py-2">
                        <div class="absolute inset-x-0 top-1/2 border-t border-dashed border-gray-200"></div>
                        <div class="relative flex justify-center">
                            <button type="button" @click="toggleRowPicker(0)" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-xl font-black text-[#1a5632] opacity-100 shadow-sm transition hover:bg-green-50 sm:opacity-0 sm:group-hover:opacity-100">+</button>
                        </div>
                        <div x-show="rowPickerIndex === 0" @click.outside="rowPickerIndex = null" x-transition class="relative z-30 mx-auto mt-2 max-w-lg rounded-2xl border border-gray-100 bg-white p-3 shadow-xl">
                            <p class="mb-2 text-xs font-extrabold uppercase tracking-widest text-gray-400">Choose Row</p>
                            <div class="grid gap-2 sm:grid-cols-3">
                                <button type="button" @click="addRow(1, 0)" class="rounded-xl border border-gray-100 p-3 text-left hover:border-[#1a5632]/30 hover:bg-green-50">
                                    <span class="block text-sm font-extrabold text-gray-800">1 Column</span>
                                    <span class="mt-2 block h-8 rounded-lg bg-gray-100"></span>
                                </button>
                                <button type="button" @click="addRow(2, 0)" class="rounded-xl border border-gray-100 p-3 text-left hover:border-[#1a5632]/30 hover:bg-green-50">
                                    <span class="block text-sm font-extrabold text-gray-800">2 Columns</span>
                                    <span class="mt-2 grid grid-cols-2 gap-1"><i class="h-8 rounded-lg bg-gray-100"></i><i class="h-8 rounded-lg bg-gray-100"></i></span>
                                </button>
                                <button type="button" @click="addRow(3, 0)" class="rounded-xl border border-gray-100 p-3 text-left hover:border-[#1a5632]/30 hover:bg-green-50">
                                    <span class="block text-sm font-extrabold text-gray-800">3 Columns</span>
                                    <span class="mt-2 grid grid-cols-3 gap-1"><i class="h-8 rounded-lg bg-gray-100"></i><i class="h-8 rounded-lg bg-gray-100"></i><i class="h-8 rounded-lg bg-gray-100"></i></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <template x-for="(row, rowIndex) in blocks" :key="row.uid">
                        <div x-show="isLayoutRowVisible(rowIndex)">
                        <section class="rounded-2xl border border-gray-200 bg-gray-50/80 p-4 transition"
                                 :id="rowEditorId(rowIndex)"
                                 :class="isSelectedEditor(rowIndex) ? 'ring-4 ring-[#1a5632]/20 border-[#1a5632]/40' : ''">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-extrabold uppercase tracking-widest text-gray-500">Row <span x-text="rowIndex + 1"></span></p>
                                    <p class="text-[11px] font-semibold text-gray-400"><span x-text="row.columns.length"></span> column layout</p>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    <button type="button" @click="moveRow(rowIndex, -1)" class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs font-bold text-gray-500">Up</button>
                                    <button type="button" @click="moveRow(rowIndex, 1)" class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs font-bold text-gray-500">Down</button>
                                    <button type="button" @click="duplicateRow(rowIndex)" class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs font-bold text-gray-500">Duplicate</button>
                                    <button type="button" @click="blocks.splice(rowIndex, 1)" class="rounded-lg border border-red-200 bg-white px-2 py-1 text-xs font-bold text-red-600">Remove</button>
                                </div>
                            </div>

                            <div class="mb-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                <select x-model="row.data.section" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600">
                                    <option value="normal">Normal section</option>
                                    <option value="hero">Hero section</option>
                                    <option value="stats">Stats bar</option>
                                    <option value="cards">Card grid</option>
                                    <option value="dark">Dark highlight</option>
                                    <option value="cta">Call to action</option>
                                </select>
                                <select x-model="row.data.width" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600">
                                    <option value="normal">Normal row</option>
                                    <option value="wide">Wide row</option>
                                    <option value="narrow">Narrow row</option>
                                </select>
                                <select x-model="row.data.gap" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600">
                                    <option value="normal">Normal spacing</option>
                                    <option value="compact">Compact spacing</option>
                                    <option value="large">Large spacing</option>
                                </select>
                                <select x-model.number="row.data.columns" @change="setRowColumns(row, row.data.columns)" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600">
                                    <option value="1">1 Column</option>
                                    <option value="2">2 Columns</option>
                                    <option value="3">3 Columns</option>
                                    <option value="4">4 Columns</option>
                                </select>
                                <select x-model="row.data.palette" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600">
                                    <option value="default">Default palette</option>
                                    <option value="light">Light palette</option>
                                    <option value="dark">Dark palette</option>
                                    <option value="green">Green palette</option>
                                    <option value="blue">Blue palette</option>
                                    <option value="amber">Amber palette</option>
                                    <option value="image">Image background</option>
                                </select>
                                <select x-model="row.data.pattern" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600">
                                    <option value="grid">Grid pattern</option>
                                    <option value="none">No pattern</option>
                                    <option value="dots">Dot pattern</option>
                                    <option value="diagonal">Diagonal pattern</option>
                                </select>
                            </div>
                            <div x-show="row.data.palette === 'image'" class="mb-3 grid gap-2 rounded-2xl border border-gray-200 bg-white p-3 lg:grid-cols-[minmax(0,1fr)_auto]">
                                <input x-model="row.data.background_image" placeholder="Section background image URL" class="min-w-0 rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                <button type="button" @click="openRowBackgroundMedia(rowIndex)" class="rounded-xl bg-[#1a5632] px-4 py-2 text-xs font-extrabold text-white">Choose / Upload</button>
                                <template x-if="row.data.background_image">
                                    <img :src="mediaUrl(row.data.background_image)" class="h-32 w-full rounded-xl border border-gray-200 object-cover lg:col-span-2">
                                </template>
                            </div>
                            <div x-show="row.data.section !== 'normal'" class="mb-3 grid gap-2 rounded-2xl border border-gray-200 bg-white p-3 lg:grid-cols-2">
                                <input x-model="row.data.eyebrow" placeholder="Small label / eyebrow" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                <input x-model="row.data.title" placeholder="Section title" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                                <textarea x-model="row.data.description" rows="2" placeholder="Section description" class="rounded-xl border border-gray-200 px-3 py-2 text-sm lg:col-span-2"></textarea>
                                <div x-show="row.data.section === 'hero'" class="space-y-2">
                                    <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto]">
                                        <input x-model="row.data.image" placeholder="Hero background image URL" class="min-w-0 rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                        <button type="button" @click="openHeroMedia(rowIndex)" class="rounded-xl bg-[#1a5632] px-4 py-2 text-xs font-extrabold text-white">Choose / Upload</button>
                                    </div>
                                    <template x-if="row.data.image">
                                        <img :src="mediaUrl(row.data.image)" class="h-32 w-full rounded-xl border border-gray-200 object-cover">
                                    </template>
                                </div>
                                <input x-show="row.data.section === 'hero'" x-model="row.data.badge" placeholder="Hero badge text" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                <input x-show="['hero','cta'].includes(row.data.section)" x-model="row.data.primary_label" placeholder="Primary button label" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                <input x-show="['hero','cta'].includes(row.data.section)" x-model="row.data.primary_url" placeholder="Primary button URL" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                <input x-show="['hero','cta'].includes(row.data.section)" x-model="row.data.secondary_label" placeholder="Secondary button label" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                <input x-show="['hero','cta'].includes(row.data.section)" x-model="row.data.secondary_url" placeholder="Secondary button URL" class="rounded-xl border border-gray-200 px-3 py-2 text-sm">
                            </div>

                            <div class="grid gap-3" :class="columnGridClass(row.columns.length)">
                                <template x-for="(column, columnIndex) in row.columns" :key="column.uid">
                                    <div class="min-w-0 rounded-2xl border border-dashed border-gray-200 bg-white p-3">
                                        <div class="mb-3 flex items-center justify-between gap-2">
                                            <p class="text-[11px] font-extrabold uppercase tracking-widest text-gray-400">Column <span x-text="columnIndex + 1"></span></p>
                                            <button type="button" @click="toggleBlockPicker(rowIndex, columnIndex)" class="rounded-lg bg-[#1a5632] px-3 py-1.5 text-xs font-extrabold text-white">+ Content</button>
                                        </div>

                                        <div x-show="blockPicker && blockPicker.row === rowIndex && blockPicker.column === columnIndex" @click.outside="blockPicker = null" x-transition class="mb-3 rounded-2xl border border-gray-100 bg-gray-50 p-3">
                                            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                                <template x-for="tool in blockTools" :key="tool.type">
                                                    <button type="button" @click="addContentBlock(tool.type, rowIndex, columnIndex)" class="flex items-center gap-2 rounded-xl border border-gray-100 bg-white px-3 py-2 text-left text-xs font-extrabold text-gray-700 hover:border-[#1a5632]/30 hover:bg-green-50">
                                                        <span class="text-base" x-text="tool.icon"></span>
                                                        <span x-text="tool.label"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="space-y-3">
                                            <template x-for="(block, blockIndex) in column.blocks" :key="block.uid">
                                                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 transition"
                                                     :id="blockEditorId(rowIndex, columnIndex, blockIndex)"
                                                     :class="isSelectedEditor(rowIndex, columnIndex, blockIndex) ? 'ring-4 ring-[#e2a024]/25 border-[#e2a024]/60 bg-amber-50/50' : ''">
                                                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                                        <div class="flex items-center gap-2">
                                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white text-base shadow-sm" x-text="toolFor(block.type).icon"></span>
                                                            <p class="text-xs font-extrabold uppercase tracking-widest text-gray-500" x-text="toolFor(block.type).label"></p>
                                                        </div>
                                                        <div class="flex flex-wrap gap-1">
                                                            <button type="button" @click="moveContentBlock(rowIndex, columnIndex, blockIndex, -1)" class="rounded border border-gray-200 bg-white px-2 py-1 text-[11px] font-bold text-gray-500">Up</button>
                                                            <button type="button" @click="moveContentBlock(rowIndex, columnIndex, blockIndex, 1)" class="rounded border border-gray-200 bg-white px-2 py-1 text-[11px] font-bold text-gray-500">Down</button>
                                                            <button type="button" @click="column.blocks.splice(blockIndex, 1)" class="rounded border border-red-200 bg-white px-2 py-1 text-[11px] font-bold text-red-600">Remove</button>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3 grid gap-2 sm:grid-cols-2">
                                                        <select x-model="block.data.align" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600">
                                                            <option value="left">Left aligned</option>
                                                            <option value="center">Centered</option>
                                                            <option value="right">Right aligned</option>
                                                        </select>
                                                        <select x-show="block.type === 'button'" x-model="block.data.style" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600">
                                                            <option value="primary">Primary button</option>
                                                            <option value="outline">Outline button</option>
                                                            <option value="dark">Dark button</option>
                                                        </select>
                                                    </div>

                                                    <template x-if="['heading','paragraph'].includes(block.type)">
                                                        <div class="mb-3 flex flex-wrap items-center gap-1 rounded-xl border border-gray-200 bg-white p-2">
                                                            <template x-for="emoji in emojis" :key="emoji">
                                                                <button type="button" @click="insertEmoji(block, emoji)" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-base hover:bg-gray-100" x-text="emoji"></button>
                                                            </template>
                                                            <span class="mx-1 h-5 border-l border-gray-200"></span>
                                                            <button type="button" @click="wrapText(block, '**')" class="rounded-lg px-2 py-1 text-xs font-black text-gray-700 hover:bg-gray-100">B</button>
                                                            <button type="button" @click="wrapText(block, '_')" class="rounded-lg px-2 py-1 text-xs font-black italic text-gray-700 hover:bg-gray-100">I</button>
                                                        </div>
                                                    </template>

                                                    <template x-if="block.type === 'heading'">
                                                        <div class="grid gap-2 sm:grid-cols-[8rem_minmax(0,1fr)]">
                                                            <select x-model="block.data.level" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-bold">
                                                                <option value="2">Heading 2</option>
                                                                <option value="3">Heading 3</option>
                                                                <option value="4">Heading 4</option>
                                                            </select>
                                                            <input x-model="block.data.text" placeholder="Heading text" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-bold">
                                                        </div>
                                                    </template>
                                                    <template x-if="block.type === 'paragraph'">
                                                        <textarea x-model="block.data.text" rows="5" placeholder="Write paragraph text" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm leading-7"></textarea>
                                                    </template>
                                                    <template x-if="block.type === 'image'">
                                                        <div class="space-y-3">
                                                            <div class="grid gap-2 2xl:grid-cols-[minmax(0,1fr)_auto]">
                                                                <input x-model="block.data.url" placeholder="Image URL" class="min-w-0 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm">
                                                                <button type="button" @click="openMedia(rowIndex, columnIndex, blockIndex, 'image')" class="rounded-xl bg-[#1a5632] px-3 py-2 text-xs font-extrabold text-white">Choose</button>
                                                            </div>
                                                            <template x-if="block.data.url">
                                                                <img :src="block.data.url" class="h-40 w-full rounded-xl border border-gray-200 object-cover">
                                                            </template>
                                                            <input x-model="block.data.caption" placeholder="Caption" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm">
                                                        </div>
                                                    </template>
                                                    <template x-if="block.type === 'video'">
                                                        <input x-model="block.data.url" placeholder="Paste YouTube, Vimeo, or embed URL" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm">
                                                    </template>
                                                    <template x-if="block.type === 'gallery'">
                                                        <div class="space-y-3">
                                                            <div class="flex flex-wrap gap-2">
                                                                <button type="button" @click="openMedia(rowIndex, columnIndex, blockIndex, 'gallery')" class="rounded-xl bg-[#1a5632] px-3 py-2 text-xs font-extrabold text-white">Add Media</button>
                                                                <button type="button" @click="block.data.images = ''" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-extrabold text-gray-600">Clear</button>
                                                            </div>
                                                            <textarea x-model="block.data.images" rows="4" placeholder="One image URL per line" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm"></textarea>
                                                            <div class="grid grid-cols-2 gap-2" x-show="galleryImages(block).length">
                                                                <template x-for="url in galleryImages(block)" :key="url">
                                                                    <img :src="url" class="aspect-square rounded-xl border border-gray-200 object-cover">
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <template x-if="block.type === 'table'">
                                                        <div x-data="tableEditor(block)" @mouseup.window="endSel()">
                                                            <!-- Toolbar Row 1: Structure -->
                                                            <div class="flex flex-wrap items-center gap-1 rounded-t-xl border border-b-0 border-gray-200 bg-gray-50 px-2 py-1.5">
                                                                <button type="button" @click="insertRowAbove()" title="Insert row above selection" class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs font-black text-gray-700 hover:bg-gray-100">&#8679; Row</button>
                                                                <button type="button" @click="insertRowBelow()" title="Insert row below selection" class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs font-black text-gray-700 hover:bg-gray-100">&#8681; Row</button>
                                                                <button type="button" @click="delSelRows()" title="Delete selected row(s)" class="rounded-lg border border-red-100 bg-white px-2 py-1 text-xs font-black text-red-500 hover:bg-red-50">&#10005; Row</button>
                                                                <span class="mx-0.5 h-4 border-l border-gray-300"></span>
                                                                <button type="button" @click="insertColLeft()" title="Insert column left of selection" class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs font-black text-gray-700 hover:bg-gray-100">&#8678; Col</button>
                                                                <button type="button" @click="insertColRight()" title="Insert column right of selection" class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs font-black text-gray-700 hover:bg-gray-100">Col &#8680;</button>
                                                                <button type="button" @click="delSelCols()" title="Delete selected column(s)" class="rounded-lg border border-red-100 bg-white px-2 py-1 text-xs font-black text-red-500 hover:bg-red-50">&#10005; Col</button>
                                                                <span class="mx-0.5 h-4 border-l border-gray-300"></span>
                                                                <button type="button" @click="doMerge()" x-show="isMultiSel()" class="rounded-lg border border-blue-200 bg-white px-2 py-1 text-xs font-black text-blue-700 hover:bg-blue-50">&#8862; Merge</button>
                                                                <button type="button" @click="doUnmerge()" x-show="hasMergeInSel()" class="rounded-lg border border-orange-200 bg-white px-2 py-1 text-xs font-black text-orange-600 hover:bg-orange-50">&#8863; Unmerge</button>
                                                                <span class="mx-0.5 h-4 border-l border-gray-300"></span>
                                                                <button type="button" @click="clearCell()" title="Clear selected cell text" class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs font-black text-gray-500 hover:bg-gray-100">Clear</button>
                                                            </div>
                                                            <!-- Toolbar Row 2: Formatting + Table options -->
                                                            <div class="mb-2 flex flex-wrap items-center gap-1 rounded-b-xl border border-gray-200 bg-gray-50 px-2 py-1.5">
                                                                <!-- Bold — mousedown.prevent keeps input focus while clicking -->
                                                                <button type="button"
                                                                        @mousedown.prevent @click="toggleBold()"
                                                                        :class="selAllBold() ? 'border-gray-400 bg-gray-200 text-gray-900' : 'border-gray-200 bg-white text-gray-700'"
                                                                        class="rounded-lg border px-2.5 py-1 text-xs font-black hover:bg-gray-100" title="Bold">B</button>
                                                                <!-- Text color -->
                                                                <label class="flex cursor-pointer items-center gap-1 rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs font-black text-gray-700 hover:bg-gray-100" title="Text color">
                                                                    <span>A</span><span :style="'border-bottom:3px solid ' + selColor()" class="inline-block w-4 leading-none">&nbsp;</span>
                                                                    <input type="color" :value="selColor()" @input="setColor($event.target.value)" class="h-5 w-6 cursor-pointer rounded border-none p-0 align-middle">
                                                                </label>
                                                                <button type="button" @mousedown.prevent @click="clearColor()" class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-[10px] text-gray-500 hover:bg-gray-100" title="Remove text color">No Color</button>
                                                                <span class="mx-0.5 h-4 border-l border-gray-300"></span>
                                                                <!-- BG color -->
                                                                <label class="flex cursor-pointer items-center gap-1 rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs font-black text-gray-700 hover:bg-gray-100" title="Cell background color">
                                                                    BG <input type="color" :value="selBg()" @input="setBg($event.target.value)" class="h-5 w-6 cursor-pointer rounded border-none p-0 align-middle">
                                                                </label>
                                                                <button type="button" @mousedown.prevent @click="clearBg()" class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-[10px] text-gray-500 hover:bg-gray-100" title="Remove background">No BG</button>
                                                                <span class="mx-0.5 h-4 border-l border-gray-300"></span>
                                                                <!-- Text alignment — clear text labels, active state highlighted -->
                                                                <span class="text-[10px] font-bold text-gray-400">Align:</span>
                                                                <button type="button"
                                                                        @mousedown.prevent @click="setAlign('left')"
                                                                        :class="selAlign() === 'left' ? 'border-blue-300 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-700'"
                                                                        class="rounded-lg border px-2.5 py-1 text-xs font-black hover:bg-blue-50" title="Align left">Left</button>
                                                                <button type="button"
                                                                        @mousedown.prevent @click="setAlign('center')"
                                                                        :class="selAlign() === 'center' ? 'border-blue-300 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-700'"
                                                                        class="rounded-lg border px-2.5 py-1 text-xs font-black hover:bg-blue-50" title="Align center">Center</button>
                                                                <button type="button"
                                                                        @mousedown.prevent @click="setAlign('right')"
                                                                        :class="selAlign() === 'right' ? 'border-blue-300 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-700'"
                                                                        class="rounded-lg border px-2.5 py-1 text-xs font-black hover:bg-blue-50" title="Align right">Right</button>
                                                                <span class="mx-0.5 h-4 border-l border-gray-300"></span>
                                                                <!-- Wrap -->
                                                                <button type="button"
                                                                        @mousedown.prevent @click="toggleWrap()"
                                                                        :class="selWrapped() ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-600'"
                                                                        class="rounded-lg border px-2 py-1 text-xs font-black hover:bg-blue-50" title="Toggle text wrap">Wrap</button>
                                                                <span class="mx-0.5 h-4 border-l border-gray-300"></span>
                                                                <!-- Table-level: border, padding, header row -->
                                                                <button type="button"
                                                                        @mousedown.prevent @click="toggleBorder()"
                                                                        :class="border ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-500'"
                                                                        class="rounded-lg border px-2 py-1 text-xs font-black hover:bg-blue-50" title="Toggle all cell borders">Border</button>
                                                                <select @change="padding = $event.target.value; sync()" class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-xs font-black text-gray-700" title="Cell padding size">
                                                                    <option value="xs" :selected="padding === 'xs'">Tiny pad</option>
                                                                    <option value="sm" :selected="padding === 'sm'">Small pad</option>
                                                                    <option value="md" :selected="padding === 'md'">Medium pad</option>
                                                                    <option value="lg" :selected="padding === 'lg'">Large pad</option>
                                                                </select>
                                                                <button type="button"
                                                                        @mousedown.prevent @click="toggleHeaderRow()"
                                                                        :class="headerRow ? 'border-green-200 bg-green-50 text-green-700' : 'border-gray-200 bg-white text-gray-600'"
                                                                        class="rounded-lg border px-2 py-1 text-xs font-black hover:bg-green-50" title="Style first row as table header">Header Row</button>
                                                            </div>
                                                            <!-- Status hint when no cell selected -->
                                                            <p x-show="!sel" class="mb-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">&#9888; Click a cell first, then use the format buttons above.</p>
                                                            <!-- Table Grid -->
                                                            <div class="overflow-auto rounded-xl border border-gray-300 bg-white" style="max-height:360px">
                                                                <table style="border-collapse:collapse;min-width:100%;table-layout:auto">
                                                                    <tbody>
                                                                        <template x-for="(row, r) in cells" :key="r">
                                                                            <tr>
                                                                                <template x-for="(cell, c) in row" :key="c">
                                                                                    <template x-if="!cell.merged">
                                                                                        <td :rowspan="cell.rowspan || 1"
                                                                                            :colspan="cell.colspan || 1"
                                                                                            :style="'background:' + (cell.bg || '#fff') + ';outline:' + (inSel(r, c) ? '2px solid #3b82f6' : (border ? '1px solid #d1d5db' : '1px dashed #e5e7eb')) + ';outline-offset:-1px;min-width:72px;vertical-align:middle'"
                                                                                            class="p-0"
                                                                                            @mousedown="startSel(r, c)"
                                                                                            @mouseover="extSel(r, c)">
                                                                                            <input x-model="cell.text"
                                                                                                   :class="[cell.bold ? 'font-bold' : 'font-normal', headerRow && r === 0 ? 'bg-gray-50' : '']"
                                                                                                   :style="'text-align:' + (cell.align || 'center') + ';background:transparent;width:100%;color:' + (cell.color || 'inherit') + ';white-space:' + (cell.wrap === false ? 'nowrap' : 'normal')"
                                                                                                   class="min-h-[32px] w-full px-2 py-1 text-sm outline-none"
                                                                                                   @input="sync()"
                                                                                                   @keydown.enter.prevent>
                                                                                        </td>
                                                                                    </template>
                                                                                </template>
                                                                            </tr>
                                                                        </template>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                            <p class="mt-1.5 text-[11px] font-semibold text-gray-400">Click to select · Drag to select range · Row 1: insert/delete structure · Row 2: format cells</p>
                                                        </div>
                                                    </template>
                                                    <template x-if="block.type === 'button'">
                                                        <div class="grid gap-2">
                                                            <input x-model="block.data.label" placeholder="Button label" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm">
                                                            <input x-model="block.data.url" placeholder="Button URL" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm">
                                                        </div>
                                                    </template>
                                                    <template x-if="block.type === 'stat'">
                                                        <div class="grid gap-2">
                                                            <input x-model="block.data.label" placeholder="Label, e.g. Students" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm">
                                                            <input x-model="block.data.value" placeholder="Value, e.g. 300+" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-bold">
                                                        </div>
                                                    </template>
                                                    <template x-if="block.type === 'feature_card'">
                                                        <div class="grid gap-2">
                                                            <input x-model="block.data.icon" placeholder="Icon / emoji" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm">
                                                            <input x-model="block.data.title" placeholder="Card title" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-bold">
                                                            <textarea x-model="block.data.text" rows="4" placeholder="Card text" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm"></textarea>
                                                        </div>
                                                    </template>
                                                    <template x-if="block.type === 'testimonial'">
                                                        <div class="grid gap-2">
                                                            <textarea x-model="block.data.quote" rows="4" placeholder="Quote / feedback" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm"></textarea>
                                                            <input x-model="block.data.name" placeholder="Name" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-bold">
                                                            <input x-model="block.data.role" placeholder="Role, e.g. Parent" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm">
                                                        </div>
                                                    </template>
                                                    <template x-if="block.type === 'html'">
                                                        <textarea x-model="block.data.html" rows="6" placeholder="Trusted HTML" class="w-full rounded-xl border border-gray-200 bg-slate-950 px-3 py-2 font-mono text-xs text-white"></textarea>
                                                    </template>
                                                </div>
                                            </template>
                                            <div x-show="column.blocks.length === 0" class="rounded-xl border-2 border-dashed border-gray-200 py-8 text-center text-xs font-bold text-gray-400">Add content to this column.</div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-white">
                                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-4 py-2">
                                    <p class="text-[11px] font-extrabold uppercase tracking-widest text-gray-400">Live Preview</p>
                                    <p class="text-[11px] font-semibold text-gray-400" x-text="previewLabel(row)"></p>
                                </div>
                                <div class="cms-builder-preview p-4" @click="handlePreviewClick($event)">
                                    <div x-html="renderRowPreview(row)"></div>
                                </div>
                            </div>
                        </section>
                        <div x-show="!advancedLayoutScope" class="group relative py-2">
                            <div class="absolute inset-x-0 top-1/2 border-t border-dashed border-gray-200"></div>
                            <div class="relative flex justify-center">
                                <button type="button" @click="toggleRowPicker(rowIndex + 1)" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 bg-white text-xl font-black text-[#1a5632] opacity-100 shadow-sm transition hover:bg-green-50 sm:opacity-0 sm:group-hover:opacity-100">+</button>
                            </div>
                            <div x-show="rowPickerIndex === rowIndex + 1" @click.outside="rowPickerIndex = null" x-transition class="relative z-30 mx-auto mt-2 max-w-lg rounded-2xl border border-gray-100 bg-white p-3 shadow-xl">
                                <p class="mb-2 text-xs font-extrabold uppercase tracking-widest text-gray-400">Choose Row</p>
                                <div class="grid gap-2 sm:grid-cols-3">
                                    <button type="button" @click="addRow(1, rowIndex + 1)" class="rounded-xl border border-gray-100 p-3 text-left hover:border-[#1a5632]/30 hover:bg-green-50">
                                        <span class="block text-sm font-extrabold text-gray-800">1 Column</span>
                                        <span class="mt-2 block h-8 rounded-lg bg-gray-100"></span>
                                    </button>
                                    <button type="button" @click="addRow(2, rowIndex + 1)" class="rounded-xl border border-gray-100 p-3 text-left hover:border-[#1a5632]/30 hover:bg-green-50">
                                        <span class="block text-sm font-extrabold text-gray-800">2 Columns</span>
                                        <span class="mt-2 grid grid-cols-2 gap-1"><i class="h-8 rounded-lg bg-gray-100"></i><i class="h-8 rounded-lg bg-gray-100"></i></span>
                                    </button>
                                    <button type="button" @click="addRow(3, rowIndex + 1)" class="rounded-xl border border-gray-100 p-3 text-left hover:border-[#1a5632]/30 hover:bg-green-50">
                                        <span class="block text-sm font-extrabold text-gray-800">3 Columns</span>
                                        <span class="mt-2 grid grid-cols-3 gap-1"><i class="h-8 rounded-lg bg-gray-100"></i><i class="h-8 rounded-lg bg-gray-100"></i><i class="h-8 rounded-lg bg-gray-100"></i></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        </div>
                    </template>
                    <div x-show="!advancedLayoutScope && blocks.length === 0" class="rounded-xl border-2 border-dashed border-gray-200 py-10 text-center text-sm font-semibold text-gray-400">Press + and choose a 1, 2, or 3 column row to start designing this page.</div>
                </div>
            </div>
        <div x-show="settingsOpen" x-transition.opacity class="fixed inset-0 z-[120] bg-gray-950/50 p-4 backdrop-blur-sm" style="display:none;">
            <div class="ml-auto flex h-full w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl" @click.outside="settingsOpen = false">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-widest text-gray-400">Page Settings</p>
                        <h2 class="text-lg font-extrabold text-gray-950">Publishing, media, and SEO</h2>
                    </div>
                    <button type="button" @click="settingsOpen = false" class="rounded-xl border border-gray-200 px-3 py-2 text-sm font-extrabold text-gray-600 hover:bg-gray-50">Close</button>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto p-5">
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <label class="mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-400">Status</label>
                <select name="status" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm font-bold">
                    <option value="draft" @selected(old('status', $page->status) === 'draft')>Draft</option>
                    <option value="published" @selected(old('status', $page->status) === 'published')>Published</option>
                </select>
                <label class="mt-4 mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-400">Template</label>
                <select name="template" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                    <option value="default" @selected(old('template', $page->template) === 'default')>Default</option>
                    <option value="wide" @selected(old('template', $page->template) === 'wide')>Wide</option>
                </select>
                <label class="mt-4 mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-400">Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $page->sort_order) }}" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                <label class="mt-4 mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-400">Featured Image</label>
                <input type="hidden" name="featured_image" x-model="featuredImage">
                <div class="space-y-2">
                    <template x-if="featuredImage">
                        <img :src="mediaUrl(featuredImage)" class="h-32 w-full rounded-xl border border-gray-200 object-cover">
                    </template>
                    <button type="button" @click="openFeaturedMedia()" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm font-extrabold text-[#1a5632] hover:bg-green-50">Choose From Media Library</button>
                    <button type="button" x-show="featuredImage" @click="featuredImage = ''" class="w-full rounded-xl border border-red-200 px-3 py-2 text-xs font-extrabold text-red-600 hover:bg-red-50">Remove Featured Image</button>
                </div>
                <p class="mt-3 text-xs font-semibold text-gray-400">Or upload a new image into the shared media library.</p>
                <input type="file" name="featured_image_file" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <p class="mb-3 text-xs font-extrabold uppercase tracking-widest text-gray-400">SEO <span x-text="activeLanguage === 'ne' ? 'नेपाली' : 'English'"></span></p>
                <div x-show="activeLanguage === 'en'">
                    <input name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" placeholder="Meta title" class="mb-3 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                    <textarea name="meta_description" rows="3" placeholder="Meta description" class="mb-3 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">{{ old('meta_description', $page->meta_description) }}</textarea>
                    <input name="meta_keywords" value="{{ old('meta_keywords', $page->meta_keywords) }}" placeholder="keywords, comma separated" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                </div>
                <div x-show="activeLanguage === 'ne'">
                    <input name="meta_title_ne" value="{{ old('meta_title_ne', $page->meta_title_ne) }}" placeholder="नेपाली मेटा शीर्षक" class="mb-3 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                    <textarea name="meta_description_ne" rows="3" placeholder="नेपाली मेटा विवरण" class="mb-3 w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">{{ old('meta_description_ne', $page->meta_description_ne) }}</textarea>
                    <input name="meta_keywords_ne" value="{{ old('meta_keywords_ne', $page->meta_keywords_ne) }}" placeholder="नेपाली शब्दहरू" class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm">
                </div>
            </div>
                </div>
                <div class="flex justify-end gap-2 border-t border-gray-100 bg-gray-50 px-5 py-4">
                    <button type="button" @click="settingsOpen = false" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-extrabold text-gray-600">Done</button>
                </div>
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div class="fixed bottom-0 left-0 right-0 z-[90] border-t border-gray-200 bg-white/95 px-4 py-2 shadow-[0_-8px_24px_rgba(15,23,42,.08)] backdrop-blur lg:left-72">
            <div class="flex items-center justify-end gap-2">
                <button type="button" @click="settingsOpen = true" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-extrabold text-gray-700">Settings</button>
                <button type="submit" form="cms-page-form" class="rounded-xl bg-[#1a5632] px-5 py-2 text-sm font-extrabold text-white hover:bg-[#0b2415]">Save Page</button>
            </div>
        </div>
    </template>

    {{-- Section layout picker — teleported to body so fixed positioning works correctly --}}
    <template x-teleport="body">
    <div x-show="addSectionPicker !== null"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         @click.outside="addSectionPicker = null; pickerAnchor = null"
         :style="pickerAnchor ? `position:fixed;top:${pickerAnchor.top}px;left:${pickerAnchor.left}px` : 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%)'"
         class="z-[500] w-80 rounded-2xl border border-gray-100 bg-white p-3 shadow-2xl"
         style="display:none;">
        <p class="mb-3 text-[10px] font-extrabold uppercase tracking-widest text-gray-400">Choose Section Layout</p>
        <div class="flex gap-2">
            <button type="button" @click="addSectionFromPreview(1)"
                class="flex-1 flex flex-col items-center gap-2 rounded-xl border-2 border-gray-100 p-3 hover:border-[#1a5632] hover:bg-green-50 transition-colors group">
                <span class="w-full flex gap-1">
                    <i class="h-10 w-full rounded bg-gray-200 group-hover:bg-[#1a5632]/20"></i>
                </span>
                <span class="text-[10px] font-extrabold text-gray-600 group-hover:text-[#1a5632]">1 Col</span>
            </button>
            <button type="button" @click="addSectionFromPreview(2)"
                class="flex-1 flex flex-col items-center gap-2 rounded-xl border-2 border-gray-100 p-3 hover:border-[#1a5632] hover:bg-green-50 transition-colors group">
                <span class="w-full flex gap-1">
                    <i class="h-10 flex-1 rounded bg-gray-200 group-hover:bg-[#1a5632]/20"></i>
                    <i class="h-10 flex-1 rounded bg-gray-200 group-hover:bg-[#1a5632]/20"></i>
                </span>
                <span class="text-[10px] font-extrabold text-gray-600 group-hover:text-[#1a5632]">2 Col</span>
            </button>
            <button type="button" @click="addSectionFromPreview(3)"
                class="flex-1 flex flex-col items-center gap-2 rounded-xl border-2 border-gray-100 p-3 hover:border-[#1a5632] hover:bg-green-50 transition-colors group">
                <span class="w-full flex gap-1">
                    <i class="h-10 flex-1 rounded bg-gray-200 group-hover:bg-[#1a5632]/20"></i>
                    <i class="h-10 flex-1 rounded bg-gray-200 group-hover:bg-[#1a5632]/20"></i>
                    <i class="h-10 flex-1 rounded bg-gray-200 group-hover:bg-[#1a5632]/20"></i>
                </span>
                <span class="text-[10px] font-extrabold text-gray-600 group-hover:text-[#1a5632]">3 Col</span>
            </button>
            <button type="button" @click="addSectionFromPreview(4)"
                class="flex-1 flex flex-col items-center gap-2 rounded-xl border-2 border-gray-100 p-3 hover:border-[#1a5632] hover:bg-green-50 transition-colors group">
                <span class="w-full flex gap-0.5">
                    <i class="h-10 flex-1 rounded bg-gray-200 group-hover:bg-[#1a5632]/20"></i>
                    <i class="h-10 flex-1 rounded bg-gray-200 group-hover:bg-[#1a5632]/20"></i>
                    <i class="h-10 flex-1 rounded bg-gray-200 group-hover:bg-[#1a5632]/20"></i>
                    <i class="h-10 flex-1 rounded bg-gray-200 group-hover:bg-[#1a5632]/20"></i>
                </span>
                <span class="text-[10px] font-extrabold text-gray-600 group-hover:text-[#1a5632]">4 Col</span>
            </button>
        </div>
    </div>
    </template>
</form>
@endsection

@push('scripts')
<style>
    .cms-builder-preview{max-height:34rem;overflow:auto}
    .cms-builder-preview--full{max-height:52rem}
    .cms-builder-preview--full .preview-row + .preview-row{margin-top:1rem}
    .cms-builder-preview .preview-insert{position:relative;display:flex;justify-content:center;padding:.75rem 0}
    .cms-builder-preview .preview-insert::before{content:'';position:absolute;left:0;right:0;top:50%;border-top:1px dashed #cbd5e1}
    .cms-builder-preview .preview-insert button{position:relative;z-index:1;display:inline-flex;height:2.25rem;width:2.25rem;align-items:center;justify-content:center;border-radius:999px;border:1px solid #d1d5db;background:#fff;color:#1a5632;font-size:1.35rem;font-weight:900;box-shadow:0 6px 16px rgba(15,23,42,.1)}
    .cms-builder-preview .preview-insert button:hover{background:#eef7f2;border-color:#1a5632}
    .cms-builder-preview .preview-row{border-radius:1rem;padding:1.25rem;background:#fff;color:#334155}
    .cms-builder-preview [data-preview-row],
    .cms-builder-preview [data-preview-block]{cursor:pointer;transition:outline-color .18s,box-shadow .18s,transform .18s}
    .cms-builder-preview [data-preview-row]:hover{outline:2px solid rgba(26,86,50,.35);outline-offset:3px}
    .cms-builder-preview [data-preview-block]:hover{outline:2px solid rgba(226,160,36,.7);outline-offset:3px;box-shadow:0 10px 24px rgba(15,23,42,.12)}
    .cms-builder-preview [data-preview-block]:active{transform:scale(.995)}
    .cms-builder-preview .preview-grid{display:grid;gap:1rem}
    .cms-builder-preview .preview-palette-dark,
    .cms-builder-preview .preview-palette-green,
    .cms-builder-preview .preview-palette-blue,
    .cms-builder-preview .preview-palette-image,
    .cms-builder-preview .preview-section-dark{color:#fff;background:#111827}
    .cms-builder-preview .preview-palette-green{background:#0b3b24}
    .cms-builder-preview .preview-palette-blue{background:#0f2f4a}
    .cms-builder-preview .preview-palette-light{background:#f7faf8}
    .cms-builder-preview .preview-palette-amber{background:#fff7e6}
    .cms-builder-preview .preview-pattern-grid{background-image:linear-gradient(90deg,rgba(148,163,184,.18) 1px,transparent 1px),linear-gradient(180deg,rgba(148,163,184,.18) 1px,transparent 1px);background-size:30px 30px}
    .cms-builder-preview .preview-pattern-dots{background-image:radial-gradient(rgba(148,163,184,.3) 1px,transparent 1.2px);background-size:18px 18px}
    .cms-builder-preview .preview-pattern-diagonal{background-image:repeating-linear-gradient(135deg,rgba(148,163,184,.16) 0 1px,transparent 1px 18px)}
    .cms-builder-preview .preview-section-hero{display:grid;gap:1.25rem;align-items:center;background:#111827;color:#fff}
    .cms-builder-preview .preview-section-stats{padding:0;background:#fff;border:1px solid #e5e7eb}
    .cms-builder-preview .preview-section-cards{background:#f7faf8}
    .cms-builder-preview .preview-section-cta{border:1px solid rgba(148,163,184,.35)}
    .cms-builder-preview .preview-eyebrow{margin:0 0 .5rem;font-size:.68rem;font-weight:900;letter-spacing:.18em;text-transform:uppercase;color:#1a5632}
    .cms-builder-preview .preview-section-hero .preview-eyebrow,
    .cms-builder-preview .preview-section-dark .preview-eyebrow,
    .cms-builder-preview .preview-palette-dark .preview-eyebrow,
    .cms-builder-preview .preview-palette-green .preview-eyebrow,
    .cms-builder-preview .preview-palette-blue .preview-eyebrow,
    .cms-builder-preview .preview-palette-image .preview-eyebrow{color:#e2a024}
    .cms-builder-preview h3{margin:0 0 .55rem;font-size:1.5rem;line-height:1.15;font-weight:900;color:inherit}
    .cms-builder-preview p{margin:0 0 .75rem}
    .cms-builder-preview .preview-btn{display:inline-flex;margin:.25rem .4rem .25rem 0;border-radius:.7rem;background:#1a5632;padding:.55rem .9rem;color:#fff;font-size:.82rem;font-weight:900}
    .cms-builder-preview .preview-btn-outline{background:#fff;color:#1a5632;border:1px solid #1a5632}
    .cms-builder-preview .preview-image{width:100%;max-height:18rem;border-radius:1rem;object-fit:cover}
    .cms-builder-preview .preview-card{border:1px solid #e5e7eb;border-radius:1rem;background:#fff;padding:1rem;box-shadow:0 8px 20px rgba(15,23,42,.05);color:#334155}
    .cms-builder-preview .preview-grid > div{min-width:0}
    .cms-builder-preview .preview-icon{display:inline-flex;width:2.25rem;height:2.25rem;align-items:center;justify-content:center;border-radius:.75rem;background:#eef7f2}
    .cms-builder-preview .preview-stat{padding:1rem;background:#fff;color:#111827}
    .cms-builder-preview .preview-stat span{display:block;font-size:.68rem;font-weight:900;letter-spacing:.16em;text-transform:uppercase;color:#1a5632}
    .cms-builder-preview .preview-stat strong{display:block;margin-top:.25rem;font-size:1.45rem}
    .cms-builder-preview table{width:100%;border-collapse:separate;border-spacing:0;overflow:hidden;border-radius:.8rem}
    .cms-builder-preview th{background:#eef7f2;color:#1f3f2d;font-weight:900}
    .cms-builder-preview th,.cms-builder-preview td{border-right:1px solid #dfe7e2;border-bottom:1px solid #dfe7e2;padding:.55rem .7rem;text-align:left}
    @media (min-width: 900px){
        .cms-builder-preview .preview-cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
        .cms-builder-preview .preview-cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
        .cms-builder-preview .preview-section-hero{grid-template-columns:minmax(0,1fr) minmax(18rem,.8fr)}
    }
</style>
<script>
function cmsPageEditor(initialBlocks, initialBlocksNe) {
    return {
        blockTools: [
            { type: 'heading', label: 'Heading', icon: '🏷️' },
            { type: 'paragraph', label: 'Paragraph', icon: '✍️' },
            { type: 'image', label: 'Image', icon: '🖼️' },
            { type: 'video', label: 'Video', icon: '🎬' },
            { type: 'gallery', label: 'Gallery', icon: '🖼️' },
            { type: 'table', label: 'Table', icon: '▦' },
            { type: 'button', label: 'Button', icon: '🔗' },
            { type: 'stat', label: 'Stat', icon: '➕' },
            { type: 'feature_card', label: 'Feature Card', icon: '⭐' },
            { type: 'testimonial', label: 'Testimonial', icon: '❝' },
            { type: 'html', label: 'HTML', icon: '</>' },
        ],
        emojis: ['✅', '⭐', '📌', '🎓', '📚', '🏫', '📅', '🔔'],
        rowPickerIndex: null,
        blockPicker: null,
        settingsOpen: false,
        activeLanguage: 'en',
        blocksEn: [],
        blocksNe: [],
        selectedEditor: null,
        inlineEditor: null,
        advancedLayoutOpen: false,
        advancedLayoutScope: null,
        addSectionPicker: null,
        pickerAnchor: null,
        get blocks() {
            return this.activeLanguage === 'ne' ? this.blocksNe : this.blocksEn;
        },
        mediaTarget: null,
        featuredImage: @js(old('featured_image', $page->featured_image)),
        init() {
            this.blocksEn = this.normalizeInitialRows(initialBlocks);
            this.blocksNe = this.normalizeInitialRows(initialBlocksNe);
        },
        switchLanguage(language) {
            this.activeLanguage = language;
            this.rowPickerIndex = null;
            this.blockPicker = null;
            this.selectedEditor = null;
            this.inlineEditor = null;
            this.advancedLayoutScope = null;
            this.addSectionPicker = null;
        },
        normalizeInitialRows(items) {
            if (!Array.isArray(items)) return [];

            return items.map((item) => {
                if (item.type === 'row') {
                    const columns = Array.isArray(item.columns) ? item.columns : [];
                    return {
                        uid: crypto.randomUUID(),
                        type: 'row',
                        data: { section: 'normal', width: 'normal', gap: 'normal', palette: 'default', pattern: 'grid', background_image: '', columns: Math.max(columns.length, 1), ...(item.data || {}) },
                        columns: columns.map((column) => ({
                            uid: crypto.randomUUID(),
                            blocks: Array.isArray(column.blocks) ? column.blocks.map((block) => this.normalizeBlock(block)) : [],
                        })),
                    };
                }

                return {
                    uid: crypto.randomUUID(),
                    type: 'row',
                    data: { section: 'normal', width: item.data?.width || 'normal', gap: 'normal', palette: 'default', pattern: 'grid', background_image: '', columns: 1 },
                    columns: [{ uid: crypto.randomUUID(), blocks: [this.normalizeBlock(item)] }],
                };
            });
        },
        normalizeBlock(block) {
            return {
                uid: crypto.randomUUID(),
                type: block.type,
                data: { align: 'left', ...(block.data || {}) },
            };
        },
        toolFor(type) {
            return this.blockTools.find((tool) => tool.type === type) || { label: type, icon: '□' };
        },
        toggleRowPicker(position) {
            this.rowPickerIndex = this.rowPickerIndex === position ? null : position;
            this.blockPicker = null;
        },
        addRow(columnCount, position = null) {
            const count = Math.min(Math.max(parseInt(columnCount, 10) || 1, 1), 4);
            const row = {
                uid: crypto.randomUUID(),
                type: 'row',
                data: { section: 'normal', width: 'normal', gap: 'normal', palette: 'default', pattern: 'grid', background_image: '', columns: count, eyebrow: '', title: '', description: '', image: '', badge: '', primary_label: '', primary_url: '', secondary_label: '', secondary_url: '' },
                columns: Array.from({ length: count }, () => ({ uid: crypto.randomUUID(), blocks: [] })),
            };
            const insertAt = Number.isInteger(position) ? position : this.blocks.length;
            this.blocks.splice(insertAt, 0, row);
            this.rowPickerIndex = null;
        },
        togglePreviewSectionPicker(position, eventOrEl = null) {
            if (this.addSectionPicker === position) {
                this.addSectionPicker = null;
                this.pickerAnchor = null;
                return;
            }
            this.addSectionPicker = position;
            const el = eventOrEl instanceof Event ? eventOrEl.currentTarget : eventOrEl;
            if (el && el.getBoundingClientRect) {
                const r = el.getBoundingClientRect();
                const left = Math.min(r.left, window.innerWidth - 296);
                this.pickerAnchor = { top: r.bottom + 8, left: Math.max(8, left) };
            } else {
                this.pickerAnchor = null;
            }
        },
        addSectionFromPreview(columnCount) {
            const insertAt = Number.isInteger(this.addSectionPicker) ? this.addSectionPicker : this.blocks.length;
            this.addRow(columnCount, insertAt);
            this.addSectionPicker = null;
            this.$nextTick(() => this.openInlineEditor(insertAt));
        },
        setRowColumns(row, columnCount) {
            const count = Math.min(Math.max(parseInt(columnCount, 10) || 1, 1), 4);
            while (row.columns.length < count) row.columns.push({ uid: crypto.randomUUID(), blocks: [] });
            while (row.columns.length > count) {
                const removed = row.columns.pop();
                if (removed && removed.blocks.length && row.columns[0]) {
                    row.columns[0].blocks.push(...removed.blocks);
                }
            }
            row.data.columns = count;
        },
        columnGridClass(count) {
            return {
                1: 'lg:grid-cols-1',
                2: 'lg:grid-cols-2',
                3: 'lg:grid-cols-3',
                4: 'lg:grid-cols-4',
            }[count] || 'lg:grid-cols-1';
        },
        toggleBlockPicker(rowIndex, columnIndex) {
            const current = this.blockPicker;
            this.blockPicker = current && current.row === rowIndex && current.column === columnIndex
                ? null
                : { row: rowIndex, column: columnIndex };
            this.rowPickerIndex = null;
        },
        addContentBlock(type, rowIndex, columnIndex) {
            const defaults = {
                heading: { text: 'New heading', level: '2', align: 'left' },
                paragraph: { text: '', align: 'left' },
                image: { url: '', alt: '', caption: '', align: 'left' },
                video: { url: '', align: 'left' },
                gallery: { images: '', align: 'left' },
                table: { rows: '', align: 'left' },
                button: { label: 'Read More', url: '#', style: 'primary', align: 'left' },
                stat: { label: 'Students', value: '300+', align: 'left' },
                feature_card: { icon: '⭐', title: 'Feature title', text: '', align: 'left' },
                testimonial: { quote: '', name: '', role: '', align: 'left' },
                html: { html: '', align: 'left' },
            };
            const column = this.blocks[rowIndex]?.columns[columnIndex];
            if (!column) return;
            column.blocks.push({ uid: crypto.randomUUID(), type, data: defaults[type] || {} });
            this.blockPicker = null;
        },
        duplicateRow(index) {
            const source = this.blocks[index];
            if (!source) return;
            const copy = JSON.parse(JSON.stringify({
                uid: crypto.randomUUID(),
                type: 'row',
                data: source.data || { width: 'normal', gap: 'normal', columns: 1 },
                columns: source.columns || [],
            }));
            copy.columns = copy.columns.map((column) => ({
                uid: crypto.randomUUID(),
                blocks: (column.blocks || []).map((block) => ({
                    uid: crypto.randomUUID(),
                    type: block.type,
                    data: block.data || {},
                })),
            }));
            this.blocks.splice(index + 1, 0, copy);
        },
        moveRow(index, direction) {
            const next = index + direction;
            if (next < 0 || next >= this.blocks.length) return;
            const item = this.blocks.splice(index, 1)[0];
            this.blocks.splice(next, 0, item);
        },
        moveContentBlock(rowIndex, columnIndex, blockIndex, direction) {
            const blocks = this.blocks[rowIndex]?.columns[columnIndex]?.blocks;
            if (!blocks) return;
            const next = blockIndex + direction;
            if (next < 0 || next >= blocks.length) return;
            const item = blocks.splice(blockIndex, 1)[0];
            blocks.splice(next, 0, item);
        },
        insertEmoji(block, emoji) {
            block.data.text = `${block.data.text || ''}${emoji}`;
        },
        wrapText(block, mark) {
            const text = block.data.text || '';
            block.data.text = text ? `${mark}${text}${mark}` : `${mark}text${mark}`;
        },
        openMedia(rowIndex, columnIndex, blockIndex, mode) {
            this.mediaTarget = { rowIndex, columnIndex, blockIndex, mode };
            window.dispatchEvent(new CustomEvent('open-media-manager', { detail: { mode } }));
        },
        openHeroMedia(rowIndex) {
            this.mediaTarget = { rowIndex, mode: 'hero' };
            window.dispatchEvent(new CustomEvent('open-media-manager', { detail: { mode: 'hero' } }));
        },
        openRowBackgroundMedia(rowIndex) {
            this.mediaTarget = { rowIndex, mode: 'row_background' };
            window.dispatchEvent(new CustomEvent('open-media-manager', { detail: { mode: 'row_background' } }));
        },
        openFeaturedMedia() {
            this.mediaTarget = { mode: 'featured' };
            window.dispatchEvent(new CustomEvent('open-media-manager', { detail: { mode: 'featured' } }));
        },
        insertSelectedMedia(detail) {
            if (!this.mediaTarget || !detail) return;
            const image = detail.image || detail;
            if (!image.url) return;

            if (this.mediaTarget.mode === 'featured') {
                this.featuredImage = image.file_path || image.url;
                this.mediaTarget = null;
                return;
            }

            if (this.mediaTarget.mode === 'hero') {
                const row = this.blocks[this.mediaTarget.rowIndex];
                if (row) row.data.image = image.url;
                this.mediaTarget = null;
                return;
            }

            if (this.mediaTarget.mode === 'row_background') {
                const row = this.blocks[this.mediaTarget.rowIndex];
                if (row) row.data.background_image = image.url;
                this.mediaTarget = null;
                return;
            }

            const block = this.blocks[this.mediaTarget.rowIndex]?.columns[this.mediaTarget.columnIndex]?.blocks[this.mediaTarget.blockIndex];
            if (!block) return;

            if (this.mediaTarget.mode === 'gallery') {
                const current = (block.data.images || '').split('\n').map((url) => url.trim()).filter(Boolean);
                if (!current.includes(image.url)) current.push(image.url);
                block.data.images = current.join('\n');
            } else {
                block.data.url = image.url;
                block.data.alt = image.caption || image.name || '';
                if (!block.data.caption && image.caption) block.data.caption = image.caption;
            }

            this.mediaTarget = null;
        },
        galleryImages(block) {
            return (block.data.images || '').split('\n').map((url) => url.trim()).filter(Boolean);
        },
        mediaUrl(path) {
            if (!path) return '';
            if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/')) return path;
            return '/' + path;
        },
        previewLabel(row) {
            const section = row.data?.section || 'normal';
            const columns = row.columns?.length || 1;
            return `${section} · ${columns} column${columns === 1 ? '' : 's'}`;
        },
        rowEditorId(rowIndex) {
            return `cms-editor-row-${this.activeLanguage}-${rowIndex}`;
        },
        blockEditorId(rowIndex, columnIndex, blockIndex) {
            return `cms-editor-block-${this.activeLanguage}-${rowIndex}-${columnIndex}-${blockIndex}`;
        },
        isSelectedEditor(rowIndex, columnIndex = null, blockIndex = null) {
            if (!this.selectedEditor || this.selectedEditor.row !== rowIndex) return false;
            if (columnIndex === null) return this.selectedEditor.column === null;
            return this.selectedEditor.column === columnIndex && this.selectedEditor.block === blockIndex;
        },
        handlePreviewClick(event) {
            const insertTarget = event.target.closest('[data-preview-insert]');
            if (insertTarget) {
                event.stopPropagation();
                const btn = insertTarget.querySelector('button') || insertTarget;
                this.togglePreviewSectionPicker(parseInt(insertTarget.dataset.previewInsert, 10), btn);
                return;
            }

            const blockTarget = event.target.closest('[data-preview-block]');
            if (blockTarget) {
                this.openInlineEditor(
                    parseInt(blockTarget.dataset.previewRow, 10),
                    parseInt(blockTarget.dataset.previewColumn, 10),
                    parseInt(blockTarget.dataset.previewBlock, 10)
                );
                return;
            }

            const rowTarget = event.target.closest('[data-preview-row]');
            if (rowTarget) {
                this.openInlineEditor(parseInt(rowTarget.dataset.previewRow, 10));
            }
        },
        openInlineEditor(rowIndex, columnIndex = null, blockIndex = null) {
            if (!Number.isInteger(rowIndex) || !this.blocks[rowIndex]) return;
            this.selectedEditor = { row: rowIndex, column: columnIndex, block: blockIndex };
            this.inlineEditor = { row: rowIndex, column: columnIndex, block: blockIndex };
            this.rowPickerIndex = null;
            this.blockPicker = null;
        },
        closeInlineEditor() {
            this.inlineEditor = null;
            this.selectedEditor = null;
        },
        showFullLayoutEditor() {
            this.advancedLayoutOpen = true;
            this.advancedLayoutScope = null;
            this.$nextTick(() => {
                document.getElementById('cms-page-layout-editor')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        },
        hideLayoutEditor() {
            this.advancedLayoutOpen = false;
            this.advancedLayoutScope = null;
            this.selectedEditor = null;
        },
        isLayoutRowVisible(rowIndex) {
            return !this.advancedLayoutScope || this.advancedLayoutScope.row === rowIndex;
        },
        selectedInlineRow() {
            return this.inlineEditor ? this.blocks[this.inlineEditor.row] : null;
        },
        selectedInlineBlock() {
            const target = this.inlineEditor;
            if (!target || target.column === null) return null;
            return this.blocks[target.row]?.columns[target.column]?.blocks[target.block] || null;
        },
        inlineEditorLabel() {
            if (!this.inlineEditor) return '';
            if (this.inlineEditor.column === null) {
                const section = this.selectedInlineRow()?.data?.section || 'section';
                return `Section · ${section}`;
            }
            return `Content · ${this.toolFor(this.selectedInlineBlock()?.type).label}`;
        },
        revealAdvancedEditor() {
            if (!this.inlineEditor) return;
            const { row, column, block } = this.inlineEditor;
            this.closeInlineEditor();
            this.selectedEditor = { row, column, block };
            this.advancedLayoutOpen = true;
            this.advancedLayoutScope = { row, column, block };
            this.$nextTick(() => {
                const id = column === null
                    ? this.rowEditorId(row)
                    : this.blockEditorId(row, column, block);
                const el = document.getElementById(id);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        },
        renderPagePreview() {
            const parts = [this.renderPreviewInsert(0)];
            (this.blocks || []).forEach((row, rowIndex) => {
                parts.push(this.renderRowPreview(row, rowIndex));
                parts.push(this.renderPreviewInsert(rowIndex + 1));
            });
            return parts.join('');
        },
        renderPreviewInsert(position) {
            return `<div class="preview-insert"><button type="button" data-preview-insert="${position}" title="Add section here">+</button></div>`;
        },
        renderRowPreview(row) {
            const rowIndex = Number.isInteger(arguments[1]) ? arguments[1] : this.blocks.indexOf(row);
            const data = row.data || {};
            const section = data.section || 'normal';
            const palette = data.palette || (section === 'dark' || section === 'hero' ? 'dark' : 'default');
            const pattern = data.pattern || 'none';
            const columns = row.columns || [];
            const style = palette === 'image' && data.background_image
                ? ` style="background-image:linear-gradient(180deg, rgba(11,36,21,.68), rgba(11,36,21,.82)), url('${this.escapeAttr(this.mediaUrl(data.background_image))}'); background-size:cover; background-position:center;"`
                : '';
            const head = this.renderPreviewHeader(data);
            const body = columns.map((column, columnIndex) => `<div>${(column.blocks || []).map((block, blockIndex) => this.renderBlockPreview(block, rowIndex, columnIndex, blockIndex)).join('') || '<p style="color:#94a3b8;font-weight:700;">Empty column</p>'}</div>`).join('');
            const image = section === 'hero' && data.image
                ? `<figure><img class="preview-image" src="${this.escapeAttr(this.mediaUrl(data.image))}" alt=""><figcaption class="preview-card"${this.heroFocusAttrs(row, rowIndex)} style="margin-top:-4rem;position:relative;margin-left:1rem;margin-right:1rem;"><span class="preview-eyebrow">${this.escapeHtml(this.heroFocusTitle(row))}</span><strong>${this.escapeHtml(this.heroFocusText(row))}</strong></figcaption></figure>`
                : '';
            const rowAttrs = rowIndex >= 0 ? ` data-preview-row="${rowIndex}" title="Click to edit this section"` : '';

            if (section === 'hero') {
                return `<section class="preview-row preview-section-hero preview-palette-${palette} preview-pattern-${pattern}"${rowAttrs}${style}><div>${head}${this.renderExtraHeroBlocks(row, rowIndex)}</div>${image}</section>`;
            }

            return `<section class="preview-row preview-section-${section} preview-palette-${palette} preview-pattern-${pattern}"${rowAttrs}${style}>${head}<div class="preview-grid preview-cols-${columns.length || 1}">${body}</div></section>`;
        },
        renderPreviewHeader(data) {
            if ((data.section || 'normal') === 'normal') return '';
            const actions = [
                data.primary_label && data.primary_url ? `<span class="preview-btn">${this.escapeHtml(data.primary_label)}</span>` : '',
                data.secondary_label && data.secondary_url ? `<span class="preview-btn preview-btn-outline">${this.escapeHtml(data.secondary_label)}</span>` : '',
            ].join('');

            return `<div style="margin-bottom:1rem;">${data.eyebrow || data.badge ? `<p class="preview-eyebrow">${this.escapeHtml(data.badge || data.eyebrow)}</p>` : ''}${data.title ? `<h3>${this.escapeHtml(data.title)}</h3>` : ''}${data.description ? `<p>${this.formatPreviewText(data.description)}</p>` : ''}${actions ? `<div>${actions}</div>` : ''}</div>`;
        },
        previewBlockAttrs(rowIndex, columnIndex, blockIndex) {
            return rowIndex >= 0
                ? ` data-preview-row="${rowIndex}" data-preview-column="${columnIndex}" data-preview-block="${blockIndex}" title="Click to edit this content block"`
                : '';
        },
        renderBlockPreview(block, rowIndex = -1, columnIndex = -1, blockIndex = -1) {
            const data = block.data || {};
            const align = ['left', 'center', 'right'].includes(data.align) ? data.align : 'left';
            const style = ` style="text-align:${align};"`;
            const attrs = this.previewBlockAttrs(rowIndex, columnIndex, blockIndex);
            if (block.type === 'heading') return `<h3${attrs}${style}>${this.formatPreviewText(data.text || 'Heading')}</h3>`;
            if (block.type === 'paragraph') return `<p${attrs}${style}>${this.formatPreviewText(data.text || 'Paragraph text')}</p>`;
            if (block.type === 'image') return data.url ? `<figure${attrs}${style}><img class="preview-image" src="${this.escapeAttr(this.mediaUrl(data.url))}" alt="">${data.caption ? `<figcaption>${this.escapeHtml(data.caption)}</figcaption>` : ''}</figure>` : `<p${attrs} style="color:#94a3b8;">Image not selected</p>`;
            if (block.type === 'video') return `<div class="preview-card"${attrs}${style}>🎬 ${this.escapeHtml(data.url || 'Video URL')}</div>`;
            if (block.type === 'gallery') return `<div class="preview-grid preview-cols-3"${attrs}>${this.galleryImages(block).slice(0, 6).map((url) => `<img class="preview-image" src="${this.escapeAttr(this.mediaUrl(url))}" alt="">`).join('') || '<p style="color:#94a3b8;">No gallery images</p>'}</div>`;
            if (block.type === 'table') return `<div${attrs}>${this.renderTablePreview(data.rows || '')}</div>`;
            if (block.type === 'button') return `<p${attrs}${style}><span class="preview-btn ${data.style === 'outline' ? 'preview-btn-outline' : ''}">${this.escapeHtml(data.label || 'Button')}</span></p>`;
            if (block.type === 'stat') return `<div class="preview-stat"${attrs}${style}><span>${this.escapeHtml(data.label || 'Label')}</span><strong>${this.escapeHtml(data.value || 'Value')}</strong></div>`;
            if (block.type === 'feature_card') return `<article class="preview-card"${attrs}${style}><div class="preview-icon">${this.escapeHtml(data.icon || '⭐')}</div><h3>${this.escapeHtml(data.title || 'Feature title')}</h3><p>${this.formatPreviewText(data.text || 'Feature text')}</p></article>`;
            if (block.type === 'testimonial') return `<article class="preview-card"${attrs}${style}><p>"${this.formatPreviewText(data.quote || 'Feedback quote')}"</p><strong>${this.escapeHtml(data.name || 'Name')}</strong><br><span>${this.escapeHtml(data.role || '')}</span></article>`;
            if (block.type === 'html') return `<div class="preview-card"${attrs}>${data.html || '<code>HTML preview</code>'}</div>`;
            return '';
        },
        renderExtraHeroBlocks(row, rowIndex = -1) {
            const blocks = [];
            (row.columns || []).forEach((column, columnIndex) => {
                (column.blocks || []).forEach((block, blockIndex) => {
                    if (block.type !== 'feature_card') {
                        blocks.push(this.renderBlockPreview(block, rowIndex, columnIndex, blockIndex));
                    }
                });
            });
            return blocks.join('');
        },
        heroFocusTitle(row) {
            const card = (row.columns || []).flatMap((column) => column.blocks || []).find((block) => block.type === 'feature_card');
            return card?.data?.title || 'Learning Focus';
        },
        heroFocusText(row) {
            const card = (row.columns || []).flatMap((column) => column.blocks || []).find((block) => block.type === 'feature_card');
            return card?.data?.text || '';
        },
        heroFocusAttrs(row, rowIndex) {
            if (rowIndex < 0) return '';
            for (let columnIndex = 0; columnIndex < (row.columns || []).length; columnIndex++) {
                const blocks = row.columns[columnIndex]?.blocks || [];
                const blockIndex = blocks.findIndex((block) => block.type === 'feature_card');
                if (blockIndex >= 0) return this.previewBlockAttrs(rowIndex, columnIndex, blockIndex);
            }
            return '';
        },
        renderTablePreview(rowsText) {
            try {
                const d = JSON.parse(rowsText || '');
                if (d && Array.isArray(d.cells) && d.cells.length) {
                    const hasBorder = d.border !== false;
                    const padMap = { xs: '3px 5px', sm: '5px 8px', md: '8px 12px', lg: '12px 16px' };
                    const pad = padMap[d.padding] || '8px 12px';
                    const headerRow = !!d.headerRow;
                    const borderStyle = hasBorder ? '1px solid #dfe7e2' : 'none';
                    let html = '<table style="border-collapse:collapse;width:100%;font-size:.88rem">';
                    d.cells.forEach((row, ri) => {
                        html += '<tr>';
                        const isHdr = headerRow && ri === 0;
                        const tag = isHdr ? 'th' : 'td';
                        (row || []).forEach((cell) => {
                            if (!cell || cell.merged) return;
                            const rs = (cell.rowspan || 1) > 1 ? ` rowspan="${cell.rowspan}"` : '';
                            const cs = (cell.colspan || 1) > 1 ? ` colspan="${cell.colspan}"` : '';
                            let s = `border:${borderStyle};padding:${pad};`;
                            if (cell.bg) s += `background:${cell.bg};`;
                            if (cell.color) s += `color:${cell.color};`;
                            if (cell.bold) s += 'font-weight:bold;';
                            const align = ['left', 'center', 'right'].includes(cell.align) ? cell.align : (isHdr ? 'center' : 'left');
                            s += `text-align:${align};`;
                            if (cell.wrap === false) s += 'white-space:nowrap;';
                            html += `<${tag}${rs}${cs} style="${s}">${this.escapeHtml(cell.text || '')}</${tag}>`;
                        });
                        html += '</tr>';
                    });
                    html += '</table>';
                    return html;
                }
            } catch (e) {}
            const rows = (rowsText || '').split('\n').map((row) => row.trim()).filter(Boolean);
            if (!rows.length) return '<p style="color:#94a3b8;">No table rows</p>';
            return `<table>${rows.map((row, index) => `<tr>${row.split(',').map((cell) => index === 0 ? `<th>${this.escapeHtml(cell.trim())}</th>` : `<td>${this.escapeHtml(cell.trim())}</td>`).join('')}</tr>`).join('')}</table>`;
        },
        formatPreviewText(text) {
            return this.escapeHtml(text || '').replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').replace(/_(.+?)_/g, '<em>$1</em>').replace(/\n/g, '<br>');
        },
        escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
        },
        escapeAttr(value) {
            return this.escapeHtml(value).replace(/`/g, '&#096;');
        },
    };
}

function tableEditor(block) {
    const DROWS = 4, DCOLS = 6;

    function makeCell() {
        return { text: '', bold: false, align: 'center', bg: '', color: '', rowspan: 1, colspan: 1, merged: false, wrap: true };
    }
    function makeGrid(rows, cols) {
        return Array.from({ length: rows }, () => Array.from({ length: cols }, makeCell));
    }

    return {
        cells: [],
        border: true,
        padding: 'md',
        headerRow: false,
        selecting: false,
        sel: null,
        selStart: null,

        init() { this.loadFromBlock(); },

        loadFromBlock() {
            try {
                const d = JSON.parse(block.data.rows || '');
                if (d && Array.isArray(d.cells) && d.cells.length) {
                    this.cells = d.cells;
                    this.border = d.border !== false;
                    this.padding = ['xs', 'sm', 'md', 'lg'].includes(d.padding) ? d.padding : 'md';
                    this.headerRow = !!d.headerRow;
                    return;
                }
            } catch (e) {}
            const csvRows = (block.data.rows || '').split('\n').map((r) => r.trim()).filter(Boolean);
            if (csvRows.length) {
                const parsed = csvRows.map((row) => row.split(',').map((text) => ({ ...makeCell(), text: text.trim() })));
                const maxCols = Math.max(...parsed.map((r) => r.length));
                this.cells = parsed.map((row) => { while (row.length < maxCols) row.push(makeCell()); return row; });
                this.sync(); return;
            }
            this.cells = makeGrid(DROWS, DCOLS);
            this.sync();
        },

        sync() {
            block.data.rows = JSON.stringify({ cells: this.cells, border: this.border, padding: this.padding, headerRow: this.headerRow });
        },

        numRows() { return this.cells.length; },
        numCols() { return this.cells[0]?.length || 0; },

        // ── Insert / Delete ─────────────────────────────────────
        insertRowAbove() {
            const at = this.sel ? this.sel.r1 : this.numRows();
            this.cells.splice(at, 0, Array.from({ length: this.numCols() }, makeCell));
            this.sel = { r1: at, c1: 0, r2: at, c2: this.numCols() - 1 };
            this.sync();
        },
        insertRowBelow() {
            const at = this.sel ? this.sel.r2 + 1 : this.numRows();
            this.cells.splice(at, 0, Array.from({ length: this.numCols() }, makeCell));
            this.sel = { r1: at, c1: 0, r2: at, c2: this.numCols() - 1 };
            this.sync();
        },
        insertColLeft() {
            const at = this.sel ? this.sel.c1 : this.numCols();
            this.cells.forEach((row) => row.splice(at, 0, makeCell()));
            this.sel = { r1: 0, c1: at, r2: this.numRows() - 1, c2: at };
            this.sync();
        },
        insertColRight() {
            const at = this.sel ? this.sel.c2 + 1 : this.numCols();
            this.cells.forEach((row) => row.splice(at, 0, makeCell()));
            this.sel = { r1: 0, c1: at, r2: this.numRows() - 1, c2: at };
            this.sync();
        },
        delSelRows() {
            if (!this.sel) return;
            const { r1, r2 } = this.sel;
            if (this.numRows() - (r2 - r1 + 1) < 1) return;
            // Unmerge cells in & spanning into deleted rows
            for (let r = 0; r < this.numRows(); r++)
                for (let c = 0; c < this.numCols(); c++) {
                    const cell = this.cells[r]?.[c];
                    if (!cell || cell.merged) continue;
                    const endR = r + (cell.rowspan || 1) - 1;
                    if ((r >= r1 && r <= r2) || (r < r1 && endR >= r1)) this.unmergeAt(r, c);
                }
            this.cells.splice(r1, r2 - r1 + 1);
            this.sel = null; this.sync();
        },
        delSelCols() {
            if (!this.sel) return;
            const { c1, c2 } = this.sel;
            if (this.numCols() - (c2 - c1 + 1) < 1) return;
            for (let r = 0; r < this.numRows(); r++)
                for (let c = 0; c < this.numCols(); c++) {
                    const cell = this.cells[r]?.[c];
                    if (!cell || cell.merged) continue;
                    const endC = c + (cell.colspan || 1) - 1;
                    if ((c >= c1 && c <= c2) || (c < c1 && endC >= c1)) this.unmergeAt(r, c);
                }
            this.cells.forEach((row) => row.splice(c1, c2 - c1 + 1));
            this.sel = null; this.sync();
        },
        clearCell() { this.activeCells().forEach((c) => { c.text = ''; }); this.sync(); },

        // ── Selection ───────────────────────────────────────────
        startSel(r, c) {
            this.selStart = { r, c };
            this.sel = { r1: r, c1: c, r2: r, c2: c };
            this.selecting = true;
        },
        extSel(r, c) {
            if (!this.selecting || !this.selStart) return;
            this.sel = { r1: Math.min(this.selStart.r, r), c1: Math.min(this.selStart.c, c), r2: Math.max(this.selStart.r, r), c2: Math.max(this.selStart.c, c) };
        },
        endSel() { this.selecting = false; },
        inSel(r, c) {
            if (!this.sel) return false;
            return r >= this.sel.r1 && r <= this.sel.r2 && c >= this.sel.c1 && c <= this.sel.c2;
        },
        isMultiSel() { return this.sel ? (this.sel.r2 > this.sel.r1 || this.sel.c2 > this.sel.c1) : false; },
        hasMergeInSel() {
            if (!this.sel) return false;
            const { r1, c1, r2, c2 } = this.sel;
            for (let r = r1; r <= r2; r++)
                for (let c = c1; c <= c2; c++)
                    if (this.cells[r]?.[c] && ((this.cells[r][c].rowspan || 1) > 1 || (this.cells[r][c].colspan || 1) > 1)) return true;
            return false;
        },
        activeCells() {
            if (!this.sel) return [];
            const { r1, c1, r2, c2 } = this.sel;
            const result = [];
            for (let r = r1; r <= r2; r++)
                for (let c = c1; c <= c2; c++)
                    if (this.cells[r]?.[c] && !this.cells[r][c].merged) result.push(this.cells[r][c]);
            return result;
        },

        // ── Merge ───────────────────────────────────────────────
        unmergeAt(r, c) {
            const cell = this.cells[r]?.[c];
            if (!cell || cell.merged) return;
            const rs = cell.rowspan || 1, cs = cell.colspan || 1;
            cell.rowspan = 1; cell.colspan = 1;
            for (let dr = 0; dr < rs; dr++)
                for (let dc = 0; dc < cs; dc++) {
                    if (dr === 0 && dc === 0) continue;
                    const t = this.cells[r + dr]?.[c + dc];
                    if (t) { t.merged = false; t.rowspan = 1; t.colspan = 1; }
                }
        },
        doMerge() {
            if (!this.sel || !this.isMultiSel()) return;
            const { r1, c1, r2, c2 } = this.sel;
            for (let r = r1; r <= r2; r++)
                for (let c = c1; c <= c2; c++)
                    if (this.cells[r]?.[c] && !this.cells[r][c].merged && ((this.cells[r][c].rowspan || 1) > 1 || (this.cells[r][c].colspan || 1) > 1))
                        this.unmergeAt(r, c);
            const origin = this.cells[r1][c1];
            origin.rowspan = r2 - r1 + 1; origin.colspan = c2 - c1 + 1; origin.merged = false;
            for (let r = r1; r <= r2; r++)
                for (let c = c1; c <= c2; c++) {
                    if (r === r1 && c === c1) continue;
                    const t = this.cells[r]?.[c];
                    if (t) { t.merged = true; t.rowspan = 1; t.colspan = 1; }
                }
            this.sel = { r1, c1, r2: r1, c2: c1 }; this.sync();
        },
        doUnmerge() { if (this.sel) { this.unmergeAt(this.sel.r1, this.sel.c1); this.sync(); } },

        // ── Cell formatting ─────────────────────────────────────
        selAllBold() { const c = this.activeCells(); return c.length > 0 && c.every((x) => x.bold); },
        toggleBold() {
            const cells = this.activeCells();
            const all = cells.every((c) => c.bold);
            cells.forEach((c) => { c.bold = !all; }); this.sync();
        },
        setAlign(a) { this.activeCells().forEach((c) => { c.align = a; }); this.sync(); },
        selAlign() { const c = this.activeCells(); return c.length ? (c[0].align || 'center') : ''; },
        setBg(color) { this.activeCells().forEach((c) => { c.bg = color; }); this.sync(); },
        clearBg() { this.activeCells().forEach((c) => { c.bg = ''; }); this.sync(); },
        selBg() { const c = this.activeCells(); return c.length ? (c[0].bg || '#ffffff') : '#ffffff'; },
        setColor(color) { this.activeCells().forEach((c) => { c.color = color; }); this.sync(); },
        clearColor() { this.activeCells().forEach((c) => { c.color = ''; }); this.sync(); },
        selColor() { const c = this.activeCells(); return c.length ? (c[0].color || '#000000') : '#000000'; },
        selWrapped() { const c = this.activeCells(); return !c.length || c.some((x) => x.wrap !== false); },
        toggleWrap() {
            const cells = this.activeCells();
            const all = cells.every((c) => c.wrap !== false);
            cells.forEach((c) => { c.wrap = !all; }); this.sync();
        },

        // ── Table-level settings ────────────────────────────────
        toggleBorder() { this.border = !this.border; this.sync(); },
        toggleHeaderRow() { this.headerRow = !this.headerRow; this.sync(); },
    };
}
</script>
@endpush
