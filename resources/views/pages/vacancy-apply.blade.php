@extends('layouts.app')

@section('title', 'Apply for ' . $vacancy->title)

@section('content')
<section class="pt-32 pb-16 bg-[#fdfbf7] min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <a href="{{ route('vacancies') }}" class="inline-flex items-center text-sm font-bold text-gray-500 hover:text-[#1a5632] mb-4">Back to vacancies</a>
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 sm:p-8">
                <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5">
                    <div>
                        <p class="text-[#e2a024] font-bold text-sm uppercase tracking-widest mb-2">Vacancy Application</p>
                        <h1 class="text-2xl sm:text-3xl font-bold text-[#0b2415]">{{ $vacancy->title }}</h1>
                        <div class="flex flex-wrap gap-2 mt-3">
                            <span class="bg-[#1a5632]/10 text-[#1a5632] text-xs font-bold px-3 py-1 rounded-full">{{ $vacancy->type }}</span>
                            @if($vacancy->department)
                                <span class="bg-[#e2a024]/10 text-[#b07d10] text-xs font-bold px-3 py-1 rounded-full">{{ $vacancy->department }}</span>
                            @endif
                            @if($vacancy->deadline)
                                <span class="bg-blue-50 text-blue-700 text-xs font-bold px-3 py-1 rounded-full">Deadline: {{ $vacancy->deadline->format('M d, Y') }}</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('account.applications.index') }}" class="inline-flex justify-center px-5 py-3 bg-gray-100 text-gray-700 rounded-xl font-bold text-sm hover:bg-gray-200 transition-colors">My Applications</a>
                </div>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-6 bg-red-50 text-red-700 p-4 rounded-xl text-sm font-bold border border-red-100">
                <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('vacancy.apply', $vacancy) }}" method="POST" enctype="multipart/form-data"
            class="vacancy-application-form grid lg:grid-cols-[280px_1fr] gap-8"
            data-vacancy-id="{{ $vacancy->id }}">
            @csrf
            <input type="hidden" name="vacancy_id" value="{{ $vacancy->id }}">

            <aside class="lg:sticky lg:top-32 h-fit">
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-bold text-[#0b2415]">Progress</h2>
                        <span id="progressPercent" class="text-sm font-bold text-[#1a5632]">0%</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden mb-5">
                        <div id="progressBar" class="h-full bg-[#1a5632] rounded-full transition-all duration-300" style="width:0%"></div>
                    </div>
                    <div class="space-y-2">
                        @foreach([
                            'basic' => 'Basic Info',
                            'personal' => 'Personal',
                            'documents' => 'Documents',
                            'qualification' => 'Qualification',
                            'motivation' => 'Motivation',
                        ] as $key => $label)
                            <a href="#section-{{ $key }}" data-progress-step="{{ $key }}" class="progress-step flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-bold text-gray-500 hover:bg-gray-50">
                                <span class="step-dot flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-[11px] text-gray-400">{{ $loop->iteration }}</span>
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 mt-5 leading-relaxed">Text fields save in this browser while you fill the form. Files must be selected before final submit.</p>
                </div>
            </aside>

            <div class="space-y-6">
                <div id="section-basic" data-section="basic" class="application-section bg-white border border-gray-100 rounded-2xl shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#0b2415] mb-5">Basic Information</h2>
                    <div class="grid sm:grid-cols-2 gap-5">
                        <x-apply-input label="Full Name" name="full_name" value="{{ old('full_name', $user->name) }}" required="true" />
                        <x-apply-input label="Email Address" name="email" type="email" value="{{ old('email', $user->email) }}" required="true" />
                        <x-apply-input label="Phone Number" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" required="true" />
                        <x-apply-input label="Current Address" name="address" value="{{ old('address') }}" />
                    </div>
                </div>

                <div id="section-personal" data-section="personal" class="application-section bg-white border border-gray-100 rounded-2xl shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#0b2415] mb-5">Personal Details</h2>
                    <div class="grid sm:grid-cols-2 gap-5">
                        <x-apply-input label="Date of Birth" name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" required="true" />
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Gender *</label>
                            <select name="gender" required class="form-control">
                                <option value="">Select Gender</option>
                                @foreach(['Male', 'Female', 'Other'] as $gender)
                                    <option value="{{ $gender }}" @selected(old('gender') === $gender)>{{ $gender }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-apply-input label="Father's Name" name="father_name" value="{{ old('father_name') }}" required="true" />
                        <x-apply-input label="Mother's Name" name="mother_name" value="{{ old('mother_name') }}" required="true" />
                        <x-apply-input label="Permanent Address" name="permanent_address" value="{{ old('permanent_address') }}" required="true" />
                        <x-apply-input label="Temporary Address" name="temporary_address" value="{{ old('temporary_address') }}" />
                    </div>
                </div>

                <div id="section-documents" data-section="documents" class="application-section bg-white border border-gray-100 rounded-2xl shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#0b2415] mb-5">Documents</h2>
                    <div class="mb-5">
                        <x-apply-input label="Citizenship Number" name="citizenship_no" value="{{ old('citizenship_no') }}" required="true" />
                    </div>
                    <div class="grid sm:grid-cols-2 gap-5">
                        <x-apply-file label="Profile Photo" name="profile_photo" accept=".jpg,.jpeg,.png" help="JPG/PNG, max 2MB" />
                        <x-apply-file label="Signature" name="signature" accept=".jpg,.jpeg,.png,.pdf" help="JPG/PNG/PDF, max 2MB" />
                        <x-apply-file label="Citizenship Front" name="citizen_front" accept=".jpg,.jpeg,.png,.pdf" help="JPG/PNG/PDF, max 4MB" />
                        <x-apply-file label="Citizenship Back" name="citizen_back" accept=".jpg,.jpeg,.png,.pdf" help="JPG/PNG/PDF, max 4MB" />
                    </div>
                </div>

                <div id="section-qualification" data-section="qualification" class="application-section bg-white border border-gray-100 rounded-2xl shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#0b2415] mb-5">Qualification & Experience</h2>
                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Highest Qualification *</label>
                            <select name="qualification" required class="form-control">
                                <option value="">Select Qualification</option>
                                @foreach(['SLC/SEE', '+2 / Intermediate', "Bachelor's Degree", "Master's Degree", 'M.Phil / Ph.D', 'Other'] as $q)
                                    <option value="{{ $q }}" @selected(old('qualification') === $q)>{{ $q }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-apply-input label="Years of Experience" name="experience" value="{{ old('experience') }}" />
                        <div class="sm:col-span-2">
                            <x-apply-file label="CV / Resume" name="cv" accept=".pdf,.doc,.docx" help="PDF/DOC/DOCX, max 5MB" />
                        </div>
                    </div>
                </div>

                <div id="section-motivation" data-section="motivation" class="application-section bg-white border border-gray-100 rounded-2xl shadow-sm p-6 sm:p-8">
                    <h2 class="text-lg font-bold text-[#0b2415] mb-5">Motivation / Cover Letter</h2>
                    <textarea name="motivation" required rows="8" minlength="50" maxlength="2000" class="form-control resize-y" placeholder="Tell us why you are the right candidate for this position...">{{ old('motivation') }}</textarea>
                    <div class="flex justify-between mt-2 text-xs text-gray-400">
                        <span>Minimum 50 characters.</span>
                        <span id="charCount">0 / 2000</span>
                    </div>
                </div>

                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 sm:p-8">
                    <div class="grid sm:grid-cols-[auto_1fr] gap-3">
                        <button type="button" id="previewButton" class="px-6 py-4 bg-white border border-[#1a5632] text-[#1a5632] font-bold rounded-xl hover:bg-green-50 transition-colors">
                            Preview Application
                        </button>
                        <button type="submit" class="py-4 bg-[#1a5632] text-white font-bold rounded-xl hover:bg-[#0b2415] hover:shadow-lg transition-all">
                            Submit Application
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<style>
    .form-control {
        width: 100%;
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
        outline: none;
        transition: border-color .2s, box-shadow .2s, background .2s;
    }
    .form-control:focus {
        border-color: #1a5632;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(26, 86, 50, .12);
    }
</style>
@endsection

@push('scripts')
<script>
    const form = document.querySelector('.vacancy-application-form');
    const storageKey = 'vacancy_application_draft_' + form.dataset.vacancyId;
    const sections = Array.from(document.querySelectorAll('.application-section'));
    const requiredFields = Array.from(form.querySelectorAll('[required]'));
    const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]));
    }

    function updateProgress() {
        const completedRequired = requiredFields.filter(field => field.type === 'file' ? field.files.length : field.value.trim()).length;
        const percent = Math.round((completedRequired / requiredFields.length) * 100);
        document.getElementById('progressPercent').textContent = percent + '%';
        document.getElementById('progressBar').style.width = percent + '%';

        sections.forEach(section => {
            const fields = Array.from(section.querySelectorAll('[required]'));
            const done = fields.every(field => field.type === 'file' ? field.files.length : field.value.trim());
            const step = document.querySelector('[data-progress-step="' + section.dataset.section + '"]');
            step?.classList.toggle('text-[#1a5632]', done);
            step?.querySelector('.step-dot')?.classList.toggle('bg-[#1a5632]', done);
            step?.querySelector('.step-dot')?.classList.toggle('text-white', done);
        });
    }

    function fileSize(file) {
        return file.size > 1024 * 1024 ? (file.size / (1024 * 1024)).toFixed(1) + ' MB' : Math.ceil(file.size / 1024) + ' KB';
    }

    form.querySelectorAll('input:not([type="file"]):not([type="hidden"]), textarea, select').forEach(field => {
        if (!field.value && saved[field.name]) field.value = saved[field.name];
        field.addEventListener('input', () => {
            saved[field.name] = field.value;
            localStorage.setItem(storageKey, JSON.stringify(saved));
            updateProgress();
        });
        field.addEventListener('change', updateProgress);
    });

    form.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', () => {
            const box = document.querySelector('[data-preview-for="' + input.name + '"]');
            const file = input.files[0];
            if (!box || !file) {
                updateProgress();
                return;
            }

            let preview = '<div class="flex items-center gap-3"><div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#1a5632]/10 text-xs font-bold text-[#1a5632]">' + (file.type.startsWith('image/') ? 'IMG' : (file.type.includes('pdf') ? 'PDF' : 'DOC')) + '</div><div class="min-w-0"><p class="truncate text-sm font-bold text-gray-800">' + escapeHtml(file.name) + '</p><p class="text-xs text-gray-400">' + fileSize(file) + '</p></div></div>';
            box.innerHTML = preview;

            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.className = 'mt-3 h-24 w-full rounded-lg object-cover border border-gray-100';
                box.appendChild(img);
            }

            updateProgress();
        });
    });

    const motivation = form.querySelector('textarea[name="motivation"]');
    const charCount = document.getElementById('charCount');
    function updateCharCount() {
        charCount.textContent = motivation.value.length + ' / 2000';
    }
    motivation.addEventListener('input', updateCharCount);

    document.getElementById('previewButton').addEventListener('click', () => {
        const data = new FormData(form);
        const rows = ['full_name', 'email', 'phone', 'gender', 'date_of_birth', 'qualification', 'experience', 'citizenship_no'].map(name => {
            return '<div class="rounded-xl bg-gray-50 p-3"><p class="text-xs font-bold uppercase text-gray-400">' + name.replaceAll('_', ' ') + '</p><p class="mt-1 text-sm font-semibold text-gray-800">' + escapeHtml(data.get(name) || 'Not provided') + '</p></div>';
        }).join('');

        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-[200] bg-black/60 p-4 flex items-center justify-center';
        modal.innerHTML = '<div class="max-h-[88vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl"><div class="flex items-center justify-between border-b border-gray-100 px-6 py-4"><h3 class="text-xl font-bold text-[#0b2415]">Application Preview</h3><button type="button" class="preview-close rounded-xl bg-gray-100 px-4 py-2 text-sm font-bold text-gray-700">Close</button></div><div class="p-6 space-y-5"><div class="grid sm:grid-cols-2 gap-3">' + rows + '</div><div><p class="mb-2 text-xs font-bold uppercase text-gray-400">Motivation</p><div class="rounded-xl bg-gray-50 p-4 text-sm whitespace-pre-line">' + escapeHtml(data.get('motivation') || 'Not provided') + '</div></div></div></div>';
        document.body.appendChild(modal);
        modal.querySelector('.preview-close').addEventListener('click', () => modal.remove());
        modal.addEventListener('click', event => { if (event.target === modal) modal.remove(); });
    });

    form.addEventListener('submit', () => localStorage.removeItem(storageKey));
    updateCharCount();
    updateProgress();
</script>
@endpush
