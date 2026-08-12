<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Store') | {{ $siteSettings->localized('site_name', 'School') }}</title>
    <link rel="icon" type="image/x-icon" href="{{ $siteSettings->faviconUrl() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @php
        $primary      = $siteSettings->get('primary_color', '#1a5632');
        $primaryLight = $siteSettings->get('primary_light_color', '#237042');
        $secondary    = $siteSettings->get('secondary_color', '#e2a024');
        $dark         = $siteSettings->get('dark_color', '#0b2415');
        $sidebarEnd   = $siteSettings->get('sidebar_gradient_end', '#050f09');
        $storeNpCal   = new \App\Http\Controllers\Hajiri\NepaliCalendarController();
        $storeTodayAd = now();
        $storeTodayBs = $storeNpCal->ad_2_bs((int) $storeTodayAd->format('Y'), (int) $storeTodayAd->format('m'), (int) $storeTodayAd->format('d')) ?: null;
        $storeBsToday = $storeTodayBs ? sprintf('%04d-%02d-%02d', $storeTodayBs['year'], $storeTodayBs['month'], $storeTodayBs['date']) : '';
        $storeTodayDow = (int) $storeTodayAd->dayOfWeek;
        $storeBsCalendar = collect($storeNpCal->bs)
            ->mapWithKeys(fn($row) => [(string) $row[0] => array_values(array_slice($row, 1, 12))])
            ->all();
        $storeBsMonths = collect(range(1, 12))
            ->mapWithKeys(fn($month) => [(string) $month => $storeNpCal->get_nepali_month($month)])
            ->all();
    @endphp
    <style>
        :root {
            --theme-primary: {{ $primary }};
            --theme-primary-light: {{ $primaryLight }};
            --theme-secondary: {{ $secondary }};
            --theme-dark: {{ $dark }};
            --theme-sidebar-bg: {{ $dark }};
            --theme-sidebar-gradient-end: {{ $sidebarEnd }};
        }
        .bg-\[\#1a5632\] { background-color: var(--theme-primary) !important; }
        .bg-\[\#0b2415\] { background-color: var(--theme-dark) !important; }
        .text-\[\#1a5632\] { color: var(--theme-primary) !important; }
        .border-\[\#1a5632\] { border-color: var(--theme-primary) !important; }
        main { min-width: 0; }
        ::selection { background-color: var(--theme-primary); color: #fff; }
        .store-date-picker-panel {
            position: fixed;
            z-index: 80;
            width: min(21rem, calc(100vw - 2rem));
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .18);
            padding: 12px;
        }
        .store-date-picker-panel button:disabled {
            cursor: not-allowed;
            opacity: .35;
        }
        .store-bs-date-wrap {
            position: relative;
            display: block;
            width: 100%;
        }
        .store-bs-date-wrap > .store-bs-date {
            padding-right: 2.65rem !important;
        }
        .store-bs-date-trigger {
            position: absolute;
            top: 50%;
            right: .45rem;
            display: inline-flex;
            width: 2rem;
            height: 2rem;
            transform: translateY(-50%);
            align-items: center;
            justify-content: center;
            border: 1px solid #e5e7eb;
            border-radius: .65rem;
            background: #fff;
            color: var(--theme-primary);
            font-size: .9rem;
            line-height: 1;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
        }
        .store-bs-date-trigger:hover {
            background: #f0fdf4;
            border-color: var(--theme-primary);
        }
    </style>
    @stack('styles')
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50" x-data="{ sidebarOpen: false }">
    <div class="flex h-dvh overflow-hidden">
        @include('store.partials.sidebar')

        <div class="relative flex flex-col flex-1 min-w-0 overflow-y-auto overflow-x-hidden" data-page-scroll-root>
            @include('backend.partials.module-header')

            <div class="px-4 sm:px-6 lg:px-8 pt-4 space-y-2">
                @if(session('success'))
                    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-800">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">{{ session('error') }}</div>
                @endif
            </div>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>

            <footer class="shrink-0 px-6 py-3 border-t border-gray-100 bg-white">
                <p class="text-xs text-gray-400 text-center">&copy; {{ date('Y') }} {{ $siteSettings->localized('site_name', 'School') }} — Store ERP</p>
            </footer>
        </div>
    </div>

    @include('partials.page-wheel-scroll')
    <script>
        window.storeBsDateConfig = {
            today: @js($storeBsToday),
            todayDow: @js($storeTodayDow),
            calendar: @js($storeBsCalendar),
            months: @js($storeBsMonths),
        };
    </script>
    <script>
        (function() {
            const config = window.storeBsDateConfig || {};
            const calendar = config.calendar || {};
            const months = config.months || {};

            const pad = value => String(value || '').padStart(2, '0');
            const dateString = (year, month, day) => `${year}-${pad(month)}-${pad(day)}`;
            const compare = (left, right) => {
                const a = normalizeFull(left);
                const b = normalizeFull(right);
                if (!a || !b) return 0;
                return a.localeCompare(b);
            };
            const normalizeFull = value => {
                const raw = String(value || '').trim();
                const parts = raw.includes('-') ? raw.split('-') : [];
                if (parts.length === 3) {
                    const y = parts[0].replace(/\D/g, '').slice(0, 4);
                    const m = parts[1].replace(/\D/g, '').slice(0, 2);
                    const d = parts[2].replace(/\D/g, '').slice(0, 2);
                    if (y.length === 4 && m && d) return `${y}-${pad(m)}-${pad(d)}`;
                }
                const digits = raw.replace(/\D/g, '').slice(0, 8);
                if (digits.length === 8) return `${digits.slice(0, 4)}-${digits.slice(4, 6)}-${digits.slice(6, 8)}`;
                return '';
            };
            const mask = raw => {
                const digits = String(raw || '').replace(/\D/g, '').slice(0, 8);
                if (digits.length > 6) return `${digits.slice(0, 4)}-${digits.slice(4, 6)}-${digits.slice(6)}`;
                if (digits.length > 4) return `${digits.slice(0, 4)}-${digits.slice(4)}`;
                return digits;
            };
            const monthDays = (year, month) => Number((calendar[String(year)] || [])[Number(month) - 1] || 0);
            const years = Object.keys(calendar).map(Number).sort((a, b) => a - b);
            const todayDow = Number(config.todayDow || 0);

            let panel = null;
            let activeInput = null;

            function relatedValue(input, attr) {
                const name = input.dataset[attr];
                if (!name) return '';
                const form = input.closest('form');
                return normalizeFull(form?.querySelector(`[name="${name}"]`)?.value || '');
            }

            function minFor(input) {
                const direct = normalizeFull(input.dataset.minBs || '');
                const related = relatedValue(input, 'minSource');
                if (direct && related) return compare(direct, related) >= 0 ? direct : related;
                return direct || related;
            }

            function maxFor(input) {
                const direct = normalizeFull(input.dataset.maxBs || '');
                const related = relatedValue(input, 'maxSource');
                if (direct && related) return compare(direct, related) <= 0 ? direct : related;
                return direct || related;
            }

            function allowed(input, value) {
                const min = minFor(input);
                const max = maxFor(input);
                return (!min || compare(value, min) >= 0) && (!max || compare(value, max) <= 0);
            }

            function serial(value) {
                const normalized = normalizeFull(value);
                if (!normalized) return null;
                const [targetYear, targetMonth, targetDay] = normalized.split('-').map(Number);
                let total = 0;
                for (const year of years) {
                    if (year >= targetYear) break;
                    for (let month = 1; month <= 12; month++) total += monthDays(year, month);
                }
                for (let month = 1; month < targetMonth; month++) total += monthDays(targetYear, month);
                return total + targetDay - 1;
            }

            function firstWeekday(year, month) {
                const todaySerial = serial(config.today);
                const firstSerial = serial(dateString(year, month, 1));
                if (todaySerial === null || firstSerial === null) return 0;
                return ((todayDow + (firstSerial - todaySerial)) % 7 + 7) % 7;
            }

            function parseInput(input) {
                const normalized = normalizeFull(input.value) || config.today || dateString(years[years.length - 1] || 2082, 1, 1);
                const [year, month] = normalized.split('-').map(Number);
                return {
                    year: calendar[String(year)] ? year : (years.includes(Number(config.today?.slice(0, 4))) ? Number(config.today.slice(0, 4)) : years[years.length - 1]),
                    month: month >= 1 && month <= 12 ? month : 1,
                };
            }

            function placePanel(input) {
                const rect = input.getBoundingClientRect();
                const top = Math.min(rect.bottom + 6, window.innerHeight - panel.offsetHeight - 12);
                const left = Math.min(rect.left, window.innerWidth - panel.offsetWidth - 12);
                panel.style.top = `${Math.max(12, top)}px`;
                panel.style.left = `${Math.max(12, left)}px`;
            }

            function render(input, year, month) {
                if (!panel) {
                    panel = document.createElement('div');
                    panel.className = 'store-date-picker-panel';
                    document.body.appendChild(panel);
                }

                const days = monthDays(year, month);
                const min = minFor(input);
                const max = maxFor(input);
                const today = normalizeFull(config.today || '');
                const leadingBlanks = firstWeekday(year, month);
                panel.innerHTML = `
                    <div class="mb-2 flex items-center gap-2">
                        <select data-role="year" class="min-w-0 flex-1 rounded-lg border border-gray-200 px-2 py-2 text-sm font-bold outline-none focus:border-[#1a5632]">
                            ${years.map(y => `<option value="${y}" ${y === year ? 'selected' : ''}>${y}</option>`).join('')}
                        </select>
                        <select data-role="month" class="min-w-0 flex-1 rounded-lg border border-gray-200 px-2 py-2 text-sm font-bold outline-none focus:border-[#1a5632]">
                            ${Array.from({ length: 12 }, (_, i) => i + 1).map(m => `<option value="${m}" ${m === month ? 'selected' : ''}>${months[String(m)] || m}</option>`).join('')}
                        </select>
                    </div>
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <button type="button" data-today="${today}" ${today && allowed(input, today) ? '' : 'disabled'} class="rounded-lg border border-green-200 bg-green-50 px-3 py-1.5 text-xs font-black text-[#1a5632] hover:bg-green-100">Today</button>
                        <span class="text-[11px] font-bold text-gray-400">${today ? `Today: ${today}` : ''}</span>
                    </div>
                    ${(min || max) ? `<div class="mb-2 rounded-lg bg-amber-50 px-2 py-1.5 text-[11px] font-bold text-amber-700">${min ? `Min: ${min}` : ''}${min && max ? ' · ' : ''}${max ? `Max: ${max}` : ''}</div>` : ''}
                    <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-black text-gray-400">
                        <span>आ</span><span>सो</span><span>मं</span><span>बु</span><span>बि</span><span>शु</span><span>श</span>
                    </div>
                    <div class="mt-1 grid grid-cols-7 gap-1">
                        ${Array.from({ length: leadingBlanks }, () => '<span></span>').join('')}
                        ${Array.from({ length: days }, (_, i) => {
                            const day = i + 1;
                            const value = dateString(year, month, day);
                            const selected = normalizeFull(input.value) === value;
                            const isToday = today === value;
                            return `<button type="button" data-date="${value}" ${allowed(input, value) ? '' : 'disabled'} class="relative rounded-lg px-2 py-1.5 text-sm font-black ${selected ? 'bg-[#1a5632] text-white' : (isToday ? 'border border-[#1a5632] bg-green-50 text-[#1a5632]' : 'text-gray-700 hover:bg-green-50')}">${day}${isToday ? '<span class="absolute bottom-1 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-[#1a5632]"></span>' : ''}</button>`;
                        }).join('')}
                    </div>
                `;

                panel.querySelector('[data-role="year"]').addEventListener('change', event => render(input, Number(event.target.value), month));
                panel.querySelector('[data-role="month"]').addEventListener('change', event => render(input, year, Number(event.target.value)));
                panel.querySelector('[data-today]')?.addEventListener('click', event => selectDate(input, event.currentTarget.dataset.today));
                panel.querySelectorAll('[data-date]').forEach(button => {
                    button.addEventListener('click', () => selectDate(input, button.dataset.date));
                });
                placePanel(input);
            }

            function selectDate(input, value) {
                if (!value || !allowed(input, value)) return;
                input.value = value;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                closePanel();
            }

            function closePanel() {
                panel?.remove();
                panel = null;
                activeInput = null;
            }

            function openPanel(input) {
                activeInput = input;
                const parsed = parseInput(input);
                render(input, parsed.year, parsed.month);
            }

            function enhanceDateInputs(root = document) {
                root.querySelectorAll('.store-bs-date:not([data-picker-enhanced])').forEach(input => {
                    input.dataset.pickerEnhanced = '1';
                    const wrapper = document.createElement('span');
                    wrapper.className = 'store-bs-date-wrap';
                    input.parentNode.insertBefore(wrapper, input);
                    wrapper.appendChild(input);

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'store-bs-date-trigger';
                    button.setAttribute('aria-label', 'Open Nepali calendar');
                    button.title = 'Open Nepali calendar';
                    button.innerHTML = '<span aria-hidden="true">▦</span>';
                    wrapper.appendChild(button);

                    button.addEventListener('pointerdown', event => {
                        event.preventDefault();
                        event.stopPropagation();
                    });
                    button.addEventListener('click', event => {
                        event.preventDefault();
                        event.stopPropagation();
                        input.focus({ preventScroll: true });
                        openPanel(input);
                    });
                });
            }

            document.addEventListener('input', event => {
                if (!event.target.classList.contains('store-bs-date')) return;
                event.target.value = mask(event.target.value);
            });

            document.addEventListener('blur', event => {
                if (!event.target.classList.contains('store-bs-date')) return;
                const normalized = normalizeFull(event.target.value);
                if (normalized) event.target.value = normalized;
            }, true);

            document.addEventListener('focusin', event => {
                if (!event.target.classList.contains('store-bs-date')) return;
                openPanel(event.target);
            });

            document.addEventListener('pointerdown', event => {
                if (event.target.classList.contains('store-bs-date')) {
                    openPanel(event.target);
                }
            });

            document.addEventListener('click', event => {
                if (!panel || event.target === activeInput || panel.contains(event.target) || event.target.closest('.store-bs-date-wrap')) return;
                closePanel();
            });

            window.addEventListener('resize', () => activeInput && panel && placePanel(activeInput));
            window.addEventListener('scroll', () => activeInput && panel && placePanel(activeInput), true);

            document.addEventListener('submit', event => {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) return;
                const invalid = Array.from(form.querySelectorAll('.store-bs-date')).find(input => {
                    const value = normalizeFull(input.value);
                    if (!value) return false;
                    input.value = value;
                    return !allowed(input, value);
                });
                if (invalid) {
                    event.preventDefault();
                    invalid.focus();
                    invalid.setCustomValidity('Selected BS date is outside allowed range.');
                    invalid.reportValidity();
                    setTimeout(() => invalid.setCustomValidity(''), 1500);
                }
            });

            document.addEventListener('DOMContentLoaded', () => enhanceDateInputs());
            document.addEventListener('alpine:init', () => {
                document.addEventListener('alpine:initialized', () => enhanceDateInputs());
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
