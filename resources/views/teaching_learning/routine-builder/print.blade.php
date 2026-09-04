<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{$routinePlan->name}}</title>
    <style>
        @page{size:A4 landscape;margin:8mm}*{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#111;margin:0}
        .toolbar{display:flex;justify-content:flex-end;margin-bottom:10px}.toolbar button{border:0;background:#174f31;color:#fff;padding:8px 16px;border-radius:6px;font-weight:700}
        .page{page-break-after:always}.page:last-child{page-break-after:auto}.head{text-align:center;margin-bottom:8px}.head h1{font-size:18px;margin:0 0 3px}.head p{font-size:10px;margin:0;color:#555}.day{font-size:12px;font-weight:800;margin:7px 0 4px;text-transform:uppercase}
        table{width:100%;border-collapse:collapse;table-layout:fixed}th,td{border:1px solid #222;padding:3px;text-align:center;vertical-align:middle}thead th{font-size:9px;background:#eee}.section{width:90px;text-align:left;font-size:9px}.section b{font-size:11px;display:block}.cell{font-size:8px;line-height:1.2;min-height:32px}.cell b{font-size:9px}.group+.group{border-top:1px dashed #777;margin-top:2px;padding-top:2px}.break{width:45px;background:#f3f3f3;font-size:8px;font-weight:800;writing-mode:vertical-rl;transform:rotate(180deg)}.legend{font-size:8px;margin-top:5px;color:#555}@media print{.toolbar{display:none}}
    </style>
</head>
<body>
<div class="toolbar"><button onclick="window.print()">Print / Save PDF</button></div>
@foreach($days as $day)
    @php
        $dayLessons=$routinePlan->lessons->where('day_of_week',$day);
        $cellLessons=collect();
        foreach($dayLessons as $scheduledLesson){
            $startPosition=$scheduledLesson->period?->position;
            $endPosition=$scheduledLesson->endPeriod?->position??$startPosition;
            foreach($routinePlan->shift->periods->where('is_break',false)->whereBetween('position',[$startPosition,$endPosition]) as $coveredPeriod){
                $cellLessons->put($scheduledLesson->section_id.':'.$coveredPeriod->id,$scheduledLesson);
            }
        }
    @endphp
    <div class="page">
        <div class="head">
            <h1>{{$routinePlan->organization->name}} — {{$routinePlan->name}}</h1>
            <p>{{$routinePlan->department->name}} · {{$routinePlan->academicYear->name}} · {{$routinePlan->shift->name}} · {{collect($routinePlan->shift->working_days)->map(fn($workingDay)=>substr($workingDay,0,3))->implode(', ')}}</p>
        </div>
        <div class="day">Section / {{$day}}</div>
        <table>
            <thead><tr><th class="section">Section</th>@foreach($routinePlan->shift->periods as $period)<th class="{{$period->is_break?'break':''}}">{{$period->name}}<br>{{date('g:i A',strtotime($period->starts_at))}}–{{date('g:i A',strtotime($period->ends_at))}}</th>@endforeach</tr></thead>
            <tbody>
            @foreach($routinePlan->sections as $section)
                <tr><th class="section"><b>{{$section->name}}</b>{{$section->group_name?:$routinePlan->department->name}}</th>
                @foreach($routinePlan->shift->periods as $period)
                    @if($period->is_break)<td class="break">BREAK</td>
                    @else
                        @php $lesson=$cellLessons->get($section->id.':'.$period->id);$isStart=$lesson&&(int)$lesson->routine_period_id===(int)$period->id; @endphp
                        <td><div class="cell">@if($lesson&&$isStart)@foreach($lesson->groups as $group)<div class="group"><b>{{$lesson->mode==='practical_split'&&$group->offering->subject->practical_code?$group->offering->subject->practical_code:$group->offering->subject->code}}</b><br>{{$group->teacher_initials}}{{$group->room?' · '.$group->room->code:''}}{{$lesson->mode==='practical_split'?' · 50%':''}}</div>@endforeach @elseif($lesson)↳ {{$lesson->groups->first()?->offering?->subject?->code}}@else—@endif</div></td>
                    @endif
                @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="legend">Teacher names are abbreviated using initials (for example, Sadanand Paneru = SP). Split cells contain two simultaneous 50% practical groups.</p>
    </div>
@endforeach
</body>
</html>
