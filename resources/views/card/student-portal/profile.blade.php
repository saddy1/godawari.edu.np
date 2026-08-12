@extends('card.student-portal.layout')
@section('title', $student->profile_completed_at ? 'My Profile' : 'Complete Profile')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-widest text-[#1a5632]">Personal Information</p>
                <h1 class="mt-2 text-2xl font-extrabold text-gray-950">{{ $student->profile_completed_at ? 'My Profile' : 'Complete Your Profile' }}</h1>
                <p class="mt-1 text-sm font-medium text-gray-500">
                    {{ $student->profile_completed_at ? 'Review your approved details. Use Update Request for corrections.' : 'Complete required contact details before using portal services.' }}
                </p>
            </div>
            @if($student->profile_completed_at)
                <a href="{{ route('student.request-update') }}" class="inline-flex justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-extrabold text-gray-700 hover:bg-gray-50">Request Correction</a>
            @endif
        </div>
    </section>

    <form method="POST" action="{{ route('student.profile.update') }}" enctype="multipart/form-data" class="grid gap-5 xl:grid-cols-[320px_1fr]">
        @csrf

        {{-- ── PHOTO SIDEBAR ── --}}
        <aside class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" x-data="cameraWidget()">

            {{-- Current photo --}}
            <div class="relative mx-auto h-56 w-44 overflow-hidden rounded-2xl border border-gray-200 bg-gray-100">
                <img id="photoPreview" src="{{ $student->photo ? $student->photo_url : '' }}"
                     alt="" class="h-full w-full object-cover {{ $student->photo ? '' : 'hidden' }}">
                <div id="photoInitial" class="{{ $student->photo ? 'hidden' : 'flex' }} h-full w-full items-center justify-center text-4xl font-extrabold text-gray-300">
                    {{ strtoupper(substr($student->first_name, 0, 1)) }}
                </div>
            </div>

            <div class="mt-4 text-center">
                <p class="text-base font-extrabold text-gray-950">{{ $student->full_name }}</p>
                <p class="mt-1 text-sm font-semibold text-gray-400">{{ $student->roll_number }} · {{ $student->stream ?: 'Class not set' }}</p>
            </div>

            {{-- Hidden file input populated either by file picker or camera capture --}}
            <input type="file" name="photo" id="photoInput" accept="image/*" class="hidden">

            <div class="mt-5 space-y-2">
                <label class="mb-1 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Profile Photo</label>

                {{-- Take Photo button (hidden on non-HTTPS since camera API is blocked) --}}
                <button type="button" @click="openCamera()" id="useCameraBtn"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-[#1a5632] bg-green-50 px-4 py-3 text-sm font-extrabold text-[#1a5632] hover:bg-green-100 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/>
                    </svg>
                    Use Camera
                </button>
                <p id="cameraHttpsNote" class="hidden text-xs font-semibold text-amber-600 text-center">
                    Camera requires HTTPS. Use "Upload from Device" below.
                </p>

                {{-- File upload fallback --}}
                <button type="button" onclick="document.getElementById('photoInput').click()"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-100 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Upload from Device
                </button>

                <p class="text-xs font-medium text-gray-400 text-center">Passport-size, max 2MB</p>
            </div>

            {{-- Filename display after file selected --}}
            <p id="fileLabel" class="mt-2 hidden text-center text-xs font-bold text-emerald-600"></p>

            {{-- ── CAMERA MODAL ── --}}
            <div x-show="open" x-cloak
                 class="fixed inset-0 z-50 flex flex-col bg-black"
                 style="display:none;">

                {{-- Header --}}
                <div class="flex flex-col gap-2 px-4 py-3 text-white bg-black/80">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-extrabold">Take Photo</p>
                        <div class="flex items-center gap-3">
                            {{-- Background mode selector --}}
                            <div x-show="segReady" x-cloak class="flex items-center gap-1">
                                <span class="text-xs font-bold text-white/50 mr-1">BG:</span>
                                @foreach(['none' => 'Off', 'white' => 'White', 'blue' => 'Blue', 'blur' => 'Blur'] as $val => $lbl)
                                <button type="button"
                                        @click="bgMode = '{{ $val }}'"
                                        :class="bgMode === '{{ $val }}' ? 'bg-white text-gray-900' : 'bg-white/20 text-white'"
                                        class="rounded-lg px-2 py-1 text-xs font-black transition-colors">
                                    {{ $lbl }}
                                </button>
                                @endforeach
                            </div>
                            <button type="button" @click="closeCamera()" class="rounded-lg p-1.5 text-white/60 hover:text-white hover:bg-white/10">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Camera selector row (only when >1 camera detected) --}}
                    <div x-show="cameras.length > 1" x-cloak class="flex items-center gap-2 overflow-x-auto pb-0.5">
                        <svg class="h-3.5 w-3.5 shrink-0 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/>
                        </svg>
                        <template x-for="(cam, i) in cameras" :key="cam.deviceId">
                            <button type="button"
                                    @click="selectCamera(cam.deviceId)"
                                    :class="selectedDeviceId === cam.deviceId ? 'bg-white text-gray-900' : 'bg-white/15 text-white/80 hover:bg-white/25'"
                                    class="shrink-0 rounded-lg px-2.5 py-1 text-xs font-bold transition-colors"
                                    x-text="cam.label || ('Camera ' + (i + 1))">
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Canvas preview --}}
                <div class="relative flex flex-1 items-center justify-center bg-black overflow-hidden">
                    <video x-ref="video" class="absolute opacity-0 pointer-events-none" playsinline autoplay muted></video>
                    <canvas x-ref="canvas"
                            :style="!captured && facingMode === 'user' ? 'transform:scaleX(-1)' : ''"
                            class="max-h-full max-w-full rounded-xl transition-transform"></canvas>

                    {{-- Loading indicator --}}
                    <div x-show="!streamReady" class="absolute inset-0 flex items-center justify-center bg-black">
                        <div class="text-center text-white">
                            <svg class="mx-auto mb-3 h-10 w-10 animate-spin opacity-50" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <p class="text-sm font-bold opacity-50">Starting camera…</p>
                        </div>
                    </div>

                    {{-- Segmentation loading badge --}}
                    <div x-show="streamReady && !segReady && !segFailed" x-cloak
                         class="absolute top-3 left-3 rounded-full bg-black/50 px-3 py-1 text-xs font-bold text-white/70">
                        Loading BG removal…
                    </div>
                </div>

                {{-- Controls --}}
                <div class="flex items-center justify-center gap-6 bg-black/80 px-6 py-5">

                    {{-- Switch camera (hidden after capture) --}}
                    <button type="button" @click="toggleCamera()" x-show="!captured"
                            class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white/30 text-white hover:bg-white/10 transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    {{-- Spacer (balance layout when switch camera hidden) --}}
                    <div x-show="captured" class="h-12 w-12"></div>

                    {{-- Capture / Retake --}}
                    <button type="button"
                            @click="captured ? retake() : capture()"
                            :class="captured ? 'bg-gray-600 hover:bg-gray-500' : 'bg-white hover:bg-gray-100'"
                            class="h-16 w-16 rounded-full font-extrabold text-gray-900 shadow-lg transition-all active:scale-95">
                        <span x-text="captured ? '↺' : ''" class="text-2xl"></span>
                        <span x-show="!captured" class="block h-12 w-12 mx-auto rounded-full border-4 border-gray-900"></span>
                    </button>

                    {{-- Use Photo --}}
                    <button type="button" @click="usePhoto()" x-show="captured" x-cloak
                            class="flex h-12 items-center gap-2 rounded-full bg-[#1a5632] px-5 font-extrabold text-white hover:bg-[#0b2415] transition-colors shadow-lg">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Use Photo
                    </button>

                </div>
            </div>
        </aside>

        {{-- ── FORM SECTIONS ── --}}
        <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 p-5">
                <h2 class="text-lg font-extrabold text-gray-950">Approved Academic Details</h2>
                <p class="mt-1 text-sm font-medium text-gray-500">These fields are controlled by the school office.</p>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                @foreach([
                    'First Name' => $student->first_name,
                    'Last Name' => $student->last_name,
                    'Roll / ID' => $student->roll_number,
                    'Class' => $student->stream,
                    'Section' => $student->section,
                    'Registration No.' => $student->registration_no,
                ] as $label => $value)
                    <div>
                        <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">{{ $label }}</label>
                        <input value="{{ $value }}" disabled class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-500">
                    </div>
                @endforeach
            </div>

            <div class="border-t border-gray-100 p-5">
                <h2 class="text-lg font-extrabold text-gray-950">Required Contact Details</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Date of Birth</label>
                        <input type="date" name="dob" value="{{ old('dob', $student->dob?->format('Y-m-d')) }}" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Mobile Number</label>
                        <input name="mobile" value="{{ old('mobile', $student->mobile) }}" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Email</label>
                        <input type="email" name="email" value="{{ old('email', $student->email) }}" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Citizenship No.</label>
                        <input name="citizenship_no" value="{{ old('citizenship_no', $student->citizenship_no) }}" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-extrabold uppercase tracking-widest text-gray-500">Batch</label>
                        <input name="batch" value="{{ old('batch', $student->batch) }}" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold focus:border-[#1a5632] focus:outline-none focus:ring-2 focus:ring-[#1a5632]/15">
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 bg-gray-50 p-5 text-right">
                <button class="rounded-xl bg-[#1a5632] px-5 py-3 text-sm font-extrabold text-white hover:bg-[#0b2415]">
                    {{ $student->profile_completed_at ? 'Save Profile' : 'Save and Continue' }}
                </button>
            </div>
        </section>
    </form>
</div>

@push('scripts')
{{-- MediaPipe Selfie Segmentation (background removal) — async so it doesn't block page load --}}
<script async src="https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation@0.1/selfie_segmentation.js" crossorigin="anonymous"></script>

<script>
// Hide camera button when browser blocks getUserMedia (HTTP on non-localhost)
if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    const btn  = document.getElementById('useCameraBtn');
    const note = document.getElementById('cameraHttpsNote');
    if (btn)  btn.style.display  = 'none';
    if (note) note.classList.remove('hidden');
}

// Wire file-picker label display
document.getElementById('photoInput').addEventListener('change', function () {
    const label = document.getElementById('fileLabel');
    if (this.files[0]) {
        label.textContent = '✓ ' + this.files[0].name;
        label.classList.remove('hidden');

        // Also update preview
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('photoPreview').src = e.target.result;
            document.getElementById('photoPreview').classList.remove('hidden');
            document.getElementById('photoInitial').classList.add('hidden');
        };
        reader.readAsDataURL(this.files[0]);
    }
});

function cameraWidget() {
    return {
        open: false,
        stream: null,
        facingMode: 'environment',
        bgMode: 'none',
        captured: false,
        streamReady: false,
        segReady: false,
        segFailed: false,
        segModel: null,
        animId: null,
        looping: false,
        cameras: [],           // list of available video input devices
        selectedDeviceId: null, // null = use facingMode, string = use specific device

        async openCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Camera not available.\n\nYour browser only allows camera access on secure (HTTPS) pages. This site is currently accessed over HTTP.\n\nPlease use "Upload from Device" to add your photo instead.');
                return;
            }
            this.open = true;
            this.captured = false;
            this.streamReady = false;
            await this.$nextTick();
            await this.startStream();
            this.initSegmentation(); // non-blocking
            this.enumerateCameras(); // non-blocking; needs stream active for labels
        },

        async enumerateCameras() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                this.cameras = devices.filter(d => d.kind === 'videoinput');
                // Mark the currently active device
                if (this.stream && !this.selectedDeviceId) {
                    const activeId = this.stream.getVideoTracks()[0]?.getSettings()?.deviceId;
                    if (activeId) this.selectedDeviceId = activeId;
                }
            } catch {}
        },

        async selectCamera(deviceId) {
            this.selectedDeviceId = deviceId;
            this.looping = false;
            cancelAnimationFrame(this.animId);
            this.streamReady = false;
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
                this.stream = null;
            }
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { deviceId: { exact: deviceId }, width: { ideal: 640 }, height: { ideal: 480 } },
                    audio: false,
                });
                const video = this.$refs.video;
                video.srcObject = this.stream;
                await new Promise(r => { video.onloadedmetadata = r; });
                await video.play();
                const settings = this.stream.getVideoTracks()[0]?.getSettings();
                if (settings?.deviceId) this.selectedDeviceId = settings.deviceId;
                if (settings?.facingMode) this.facingMode = settings.facingMode;
                this.streamReady = true;
                this.startDrawLoop();
            } catch (e) {
                alert('Could not open selected camera.\n' + e.message);
            }
        },

        async startStream() {
            this.streamReady = false;
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
                this.stream = null;
            }
            try {
                let stream;
                if (this.selectedDeviceId) {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { deviceId: { exact: this.selectedDeviceId }, width: { ideal: 640 }, height: { ideal: 480 } },
                        audio: false,
                    });
                } else {
                    // Try exact facingMode first — this reliably opens front/rear on phones.
                    // Fall back to no facingMode constraint if the device doesn't support it.
                    try {
                        stream = await navigator.mediaDevices.getUserMedia({
                            video: { facingMode: { exact: this.facingMode }, width: { ideal: 640 }, height: { ideal: 480 } },
                            audio: false,
                        });
                    } catch (e2) {
                        if (e2.name === 'OverconstrainedError' || e2.name === 'ConstraintNotSatisfiedError') {
                            stream = await navigator.mediaDevices.getUserMedia({
                                video: { width: { ideal: 640 }, height: { ideal: 480 } },
                                audio: false,
                            });
                        } else {
                            throw e2;
                        }
                    }
                }
                this.stream = stream;
                const video = this.$refs.video;
                video.srcObject = stream;
                await new Promise(r => { video.onloadedmetadata = r; });
                await video.play();
                // Read actual device & facing mode from the opened track
                const settings = stream.getVideoTracks()[0]?.getSettings();
                if (settings?.deviceId) this.selectedDeviceId = settings.deviceId;
                if (settings?.facingMode) this.facingMode = settings.facingMode;
                this.streamReady = true;
                this.startDrawLoop();
            } catch (e) {
                alert('Camera access denied or unavailable.\n' + e.message);
                this.open = false;
            }
        },

        async initSegmentation() {
            if (this.segModel || typeof SelfieSegmentation === 'undefined') {
                if (typeof SelfieSegmentation === 'undefined') this.segFailed = true;
                return;
            }
            try {
                this.segModel = new SelfieSegmentation({
                    locateFile: f => `https://cdn.jsdelivr.net/npm/@mediapipe/selfie_segmentation@0.1/${f}`,
                });
                this.segModel.setOptions({ modelSelection: 1 });
                this.segModel.onResults(r => this.drawWithBg(r));
                await this.segModel.initialize();
                this.segReady = true;
            } catch (e) {
                this.segFailed = true;
                console.warn('Selfie segmentation unavailable:', e);
            }
        },

        startDrawLoop() {
            cancelAnimationFrame(this.animId);
            this.looping = true;
            const tick = async () => {
                if (!this.open || !this.looping || this.captured) return;
                const video = this.$refs.video;
                const canvas = this.$refs.canvas;
                if (!video || !canvas || video.readyState < 2) {
                    this.animId = requestAnimationFrame(tick);
                    return;
                }
                canvas.width  = video.videoWidth  || 640;
                canvas.height = video.videoHeight || 480;

                if (this.segReady && this.segModel && this.bgMode !== 'none') {
                    try { await this.segModel.send({ image: video }); } catch {}
                } else {
                    this.drawRaw(video, canvas);
                }
                this.animId = requestAnimationFrame(tick);
            };
            tick();
        },

        drawRaw(video, canvas) {
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        },

        drawWithBg(results) {
            const canvas = this.$refs.canvas;
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            const w = canvas.width, h = canvas.height;

            ctx.clearRect(0, 0, w, h);

            // Draw background
            if (this.bgMode === 'white') {
                ctx.fillStyle = '#f0f0f0';
                ctx.fillRect(0, 0, w, h);
            } else if (this.bgMode === 'blue') {
                ctx.fillStyle = '#b8d4f0';
                ctx.fillRect(0, 0, w, h);
            } else if (this.bgMode === 'blur') {
                ctx.filter = 'blur(20px)';
                ctx.drawImage(results.image, 0, 0, w, h);
                ctx.filter = 'none';
            } else {
                ctx.drawImage(results.image, 0, 0, w, h);
            }

            // Cut out person using segmentation mask
            const off = new OffscreenCanvas(w, h);
            const oc  = off.getContext('2d');
            oc.drawImage(results.image, 0, 0, w, h);
            oc.globalCompositeOperation = 'destination-in';
            oc.drawImage(results.segmentationMask, 0, 0, w, h);
            ctx.drawImage(off, 0, 0);
        },

        async toggleCamera() {
            // Check if the active track reports facingMode (phones do; Mac/desktop usually don't)
            const reportedFacing = this.stream?.getVideoTracks()[0]?.getSettings()?.facingMode;

            if (reportedFacing) {
                // Phone / tablet: flip front ↔ rear using the facingMode API
                this.facingMode = reportedFacing === 'user' ? 'environment' : 'user';
                this.selectedDeviceId = null;
                this.looping = false;
                cancelAnimationFrame(this.animId);
                await this.startStream();
            } else if (this.cameras.length > 1) {
                // Desktop / Mac: facingMode not reported — cycle through enumerated devices
                const currentIdx = this.cameras.findIndex(c => c.deviceId === this.selectedDeviceId);
                const nextIdx = (currentIdx + 1) % this.cameras.length;
                await this.selectCamera(this.cameras[nextIdx].deviceId);
            } else {
                // Last resort: try facingMode toggle blind
                this.facingMode = this.facingMode === 'user' ? 'environment' : 'user';
                this.selectedDeviceId = null;
                this.looping = false;
                cancelAnimationFrame(this.animId);
                await this.startStream();
            }
        },

        capture() {
            const canvas = this.$refs.canvas;
            if (!canvas) return;
            const w = canvas.width, h = canvas.height;

            // Always create an output canvas.
            // For front camera the live preview is CSS-mirrored (scaleX(-1)) so we must
            // flip the pixel data to produce a correctly-oriented photo.
            const out = document.createElement('canvas');
            out.width  = w;
            out.height = h;
            const oc = out.getContext('2d');
            if (this.facingMode === 'user') {
                oc.translate(w, 0);
                oc.scale(-1, 1);
            }
            oc.drawImage(canvas, 0, 0);

            this._capturedCanvas = out;

            // Update the display canvas with the correctly-oriented image so the frozen
            // preview matches after the CSS mirror is removed (captured=true).
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, w, h);
            ctx.drawImage(out, 0, 0);

            this.looping = false;
            cancelAnimationFrame(this.animId);
            this.captured = true;
        },

        retake() {
            this.captured = false;
            this._capturedCanvas = null;
            this.startDrawLoop();
        },

        async usePhoto() {
            const src = this._capturedCanvas || this.$refs.canvas;
            if (!src) return;

            src.toBlob(blob => {
                const file = new File([blob], 'photo.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(file);
                document.getElementById('photoInput').files = dt.files;

                const url = URL.createObjectURL(blob);
                const preview = document.getElementById('photoPreview');
                preview.src = url;
                preview.classList.remove('hidden');
                document.getElementById('photoInitial').classList.add('hidden');

                const label = document.getElementById('fileLabel');
                label.textContent = '✓ Photo captured from camera';
                label.classList.remove('hidden');

                this.closeCamera();
            }, 'image/jpeg', 0.92);
        },

        closeCamera() {
            this.looping = false;
            cancelAnimationFrame(this.animId);
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
                this.stream = null;
            }
            this.open = false;
            this.captured = false;
        },
    };
}
</script>
@endpush
@endsection
