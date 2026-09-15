{{-- resources/views/backend/founder-dashboard/analysis.blade.php --}}
@extends('layouts.admin')

@section('title', 'Detailed Analysis')

@section('content')

    <div class="mb-5">
        <p class="text-sm font-bold text-gray-500">Ranked over the last {{ $daysScanned }} school day{{ $daysScanned === 1 ? '' : 's' }} with recorded attendance.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">

        {{-- Teacher compliance ranking --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <h3 class="mb-1 text-sm font-black text-gray-900">Teacher Attendance-Marking Compliance</h3>
            <p class="mb-3 text-[10px] font-semibold text-gray-400">Lowest compliance first — these teachers most often skip or delay marking attendance.</p>
            <div class="max-h-[28rem] space-y-1.5 overflow-y-auto pr-1">
                @forelse($teacherRanking as $teacher)
                    @php
                        $tone = $teacher->rate >= 90
                            ? ['bar' => 'bg-emerald-500', 'text' => 'text-emerald-700']
                            : ($teacher->rate >= 70 ? ['bar' => 'bg-amber-500', 'text' => 'text-amber-700'] : ['bar' => 'bg-red-500', 'text' => 'text-red-700']);
                    @endphp
                    <div class="flex items-center gap-3 rounded-xl bg-gray-50 px-3 py-2.5">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-extrabold text-gray-900">{{ $teacher->name }}</p>
                            <p class="text-[10px] font-semibold text-gray-400">{{ $teacher->taken }} / {{ $teacher->scheduled }} classes taken</p>
                        </div>
                        <div class="h-2 w-24 shrink-0 overflow-hidden rounded-full bg-gray-200">
                            <div class="h-full rounded-full {{ $tone['bar'] }}" style="width: {{ $teacher->rate }}%"></div>
                        </div>
                        <span class="w-10 shrink-0 text-right text-xs font-black {{ $tone['text'] }}">{{ $teacher->rate }}%</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-xs font-bold text-gray-400">No attendance history yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Section attendance ranking --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <h3 class="mb-1 text-sm font-black text-gray-900">Section Attendance Ranking</h3>
            <p class="mb-3 text-[10px] font-semibold text-gray-400">Lowest attendance rate first — sections that may need intervention.</p>
            <div class="max-h-[28rem] space-y-1.5 overflow-y-auto pr-1">
                @forelse($sectionRanking as $section)
                    @php
                        $tone = $section->rate >= 90
                            ? ['bar' => 'bg-emerald-500', 'text' => 'text-emerald-700']
                            : ($section->rate >= 70 ? ['bar' => 'bg-amber-500', 'text' => 'text-amber-700'] : ['bar' => 'bg-red-500', 'text' => 'text-red-700']);
                    @endphp
                    <div class="flex items-center gap-3 rounded-xl bg-gray-50 px-3 py-2.5">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-extrabold text-gray-900">{{ $section->label }}</p>
                            <p class="text-[10px] font-semibold text-gray-400">{{ $section->total }} student-days recorded</p>
                        </div>
                        <div class="h-2 w-24 shrink-0 overflow-hidden rounded-full bg-gray-200">
                            <div class="h-full rounded-full {{ $tone['bar'] }}" style="width: {{ $section->rate }}%"></div>
                        </div>
                        <span class="w-10 shrink-0 text-right text-xs font-black {{ $tone['text'] }}">{{ $section->rate }}%</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-xs font-bold text-gray-400">No attendance history yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- 30-day trend --}}
    <div class="mt-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
        <h3 class="mb-3 text-sm font-black text-gray-900">30-Day Attendance &amp; Compliance Trend</h3>
        @php
            $points = collect($trend['labels'])->count();
            $w = 760; $h = 160; $pad = 8;
            $stepX = $points > 1 ? ($w - $pad * 2) / ($points - 1) : 0;
            $toXY = fn ($i, $v) => [$pad + $i * $stepX, $pad + (1 - $v / 100) * ($h - $pad * 2)];
            $lineFor = function ($series) use ($toXY) {
                $pts = [];
                foreach ($series as $i => $v) {
                    if ($v === null) continue;
                    $pts[] = $toXY($i, $v);
                }
                return $pts;
            };
            $attendancePts = $lineFor($trend['attendance']);
            $compliancePts = $lineFor($trend['compliance']);
            $toPath = fn ($pts) => collect($pts)->map(fn ($p, $i) => ($i === 0 ? 'M' : 'L').round($p[0], 1).','.round($p[1], 1))->implode(' ');
        @endphp
        @if(count($attendancePts) >= 2)
            <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full" role="img" aria-label="30-day attendance and compliance trend">
                @foreach([25, 50, 75] as $gridPct)
                    <line x1="{{ $pad }}" y1="{{ $pad + (1 - $gridPct / 100) * ($h - $pad * 2) }}" x2="{{ $w - $pad }}" y2="{{ $pad + (1 - $gridPct / 100) * ($h - $pad * 2) }}" stroke="#f1f5f9" stroke-width="1"/>
                @endforeach
                <path d="{{ $toPath($attendancePts) }}" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                @if(count($compliancePts) >= 2)
                    <path d="{{ $toPath($compliancePts) }}" fill="none" stroke="#e2a024" stroke-width="2" stroke-dasharray="4 3" stroke-linecap="round" stroke-linejoin="round"/>
                @endif
            </svg>
            <div class="mt-2 flex items-center gap-4 text-[10px] font-bold text-gray-500">
                <span class="flex items-center gap-1.5"><span class="h-0.5 w-3 rounded-full bg-emerald-600"></span> Attendance Rate</span>
                <span class="flex items-center gap-1.5"><span class="h-0.5 w-3 rounded-full bg-[#e2a024]"></span> Teacher Compliance</span>
            </div>
        @else
            <p class="py-8 text-center text-xs font-bold text-gray-400">Not enough attendance history yet to chart a trend.</p>
        @endif
    </div>

@endsection
