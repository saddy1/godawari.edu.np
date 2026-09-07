@php
    $sharedNpCal = new \App\Http\Controllers\Hajiri\NepaliCalendarController();
    $sharedTodayAd = now();
    $sharedTodayBsData = $sharedNpCal->ad_2_bs((int) $sharedTodayAd->format('Y'), (int) $sharedTodayAd->format('m'), (int) $sharedTodayAd->format('d')) ?: null;
    $sharedTodayBs = $sharedTodayBsData
        ? sprintf('%04d-%02d-%02d', $sharedTodayBsData['year'], $sharedTodayBsData['month'], $sharedTodayBsData['date'])
        : '';
    $sharedBsCalendar = collect($sharedNpCal->bs)
        ->mapWithKeys(fn ($row) => [(string) $row[0] => array_values(array_slice($row, 1, 12))])
        ->all();
    $sharedBsMonths = collect(range(1, 12))
        ->mapWithKeys(fn ($month) => [(string) $month => $sharedNpCal->get_nepali_month($month)])
        ->all();
@endphp
<style>
    .nepali-date-picker-panel { position:fixed; inset:auto; z-index:2147483647; width:min(21rem,calc(100vw - 2rem)); height:auto; margin:0; padding:12px; border:1px solid #e5e7eb; border-radius:14px; background:#fff; box-shadow:0 18px 45px rgba(15,23,42,.28); }
    .nepali-date-picker-head { display:flex; gap:.5rem; align-items:center; margin-bottom:.5rem; }
    .nepali-date-picker-head select { min-width:0; flex:1; height:2.4rem; border:1px solid #e5e7eb; border-radius:.55rem; background:#fff; color:#1f2937; font-size:.875rem; font-weight:800; padding:0 .5rem; outline:none; }
    .nepali-date-picker-head select:focus { border-color:var(--theme-primary,#1a5632); }
    .nepali-date-picker-today-row { display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-bottom:.5rem; }
    .nepali-date-picker-today-row button { border:1px solid #bbf7d0; border-radius:.5rem; background:#f0fdf4; color:var(--theme-primary,#1a5632); padding:.375rem .75rem; font-size:.75rem; font-weight:900; }
    .nepali-date-picker-today-row span { color:#9ca3af; font-size:.68rem; font-weight:800; }
    .nepali-date-picker-limit { margin-bottom:.5rem; border-radius:.5rem; background:#fffbeb; color:#b45309; padding:.4rem .5rem; font-size:.68rem; font-weight:800; }
    .nepali-date-picker-week, .nepali-date-picker-days { display:grid; grid-template-columns:repeat(7,1fr); gap:.25rem; text-align:center; }
    .nepali-date-picker-week { margin:.75rem 0 .3rem; color:#9ca3af; font-size:.7rem; font-weight:900; }
    .nepali-date-picker-days button { position:relative; min-height:2rem; border:0; border-radius:.55rem; background:#fff; color:#374151; font-size:.78rem; font-weight:800; }
    .nepali-date-picker-panel button:disabled { cursor:not-allowed; opacity:.35; }
    .nepali-date-picker-days button:hover { background:#ecfdf5; color:var(--theme-primary,#1a5632); }
    .nepali-date-picker-days button.today { box-shadow:inset 0 0 0 1px var(--theme-primary,#1a5632); }
    .nepali-date-picker-days button.selected { background:var(--theme-primary,#1a5632); color:#fff; box-shadow:none; }
    .nepali-date-picker-wrap { position:relative; display:block; width:100%; }
    .nepali-date-picker-wrap > input { padding-right:3rem !important; }
    .nepali-date-picker-trigger { position:absolute; top:50%; right:.5rem; transform:translateY(-50%); display:flex; width:2rem; height:2rem; align-items:center; justify-content:center; border:1px solid #e5e7eb; border-radius:.6rem; background:#fff; cursor:pointer; font-size:.9rem; }
</style>
<script>
    window.nepaliDatePickerConfig = {
        today: @js($sharedTodayBs),
        todayAd: @js($sharedTodayAd->format('Y-m-d')),
        todayDow: @js((int) $sharedTodayAd->dayOfWeek),
        calendar: @js($sharedBsCalendar),
        months: @js($sharedBsMonths),
    };
</script>
<script src="{{ asset('js/nepali-date.js') }}?v={{ filemtime(public_path('js/nepali-date.js')) }}"></script>
