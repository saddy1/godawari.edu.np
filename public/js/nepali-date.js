// BS date input mask — digits only, auto-hyphens: YYYY-MM-DD
function bsDateInput(initVal) {
    return {
        fmt(raw) {
            const d = (raw || '').replace(/\D/g, '').slice(0, 8);
            if (d.length > 6) return d.slice(0,4) + '-' + d.slice(4,6) + '-' + d.slice(6);
            if (d.length > 4) return d.slice(0,4) + '-' + d.slice(4);
            return d;
        },

        // beforeinput fires BEFORE the character appears — most reliable block point.
        onBeforeInput(e) {
            if (e.data && /\D/.test(e.data)) e.preventDefault();
        },

        // Safety net: strip any non-digit that still slipped through.
        onInput(e) {
            const next = this.fmt(e.target.value);
            if (e.target.value !== next) e.target.value = next;
        },

        onPaste(e) {
            const next = this.fmt(e.clipboardData?.getData('text') || '');
            e.target.value = next;
        },

        init() {
            // Set initial value directly on the DOM element.
            this.$nextTick(() => {
                if (this.$refs.bs) this.$refs.bs.value = this.fmt(initVal || '');
            });
        },
    };
}

// Shared Bikram Sambat calendar picker used by HR and student profile forms.
(function () {
    const pad = value => String(value).padStart(2, '0');
    const normalized = value => {
        const digits = String(value || '').replace(/\D/g, '').slice(0, 8);
        return digits.length === 8
            ? `${digits.slice(0, 4)}-${digits.slice(4, 6)}-${digits.slice(6, 8)}`
            : '';
    };

    function initNepaliDatePickers() {
        const config = window.nepaliDatePickerConfig || {};
        const calendar = config.calendar || {};
        const months = config.months || {};
        const years = Object.keys(calendar).map(Number).sort((a, b) => a - b);
        if (!years.length) return;

        let panel = null;
        let activeInput = null;
        const daysInMonth = (year, month) => Number((calendar[String(year)] || [])[month - 1] || 0);
        const dateValue = (year, month, day) => `${year}-${pad(month)}-${pad(day)}`;
        const serialDay = value => {
            const parsed = normalized(value);
            if (!parsed) return null;
            const [year, month, day] = parsed.split('-').map(Number);
            if (!calendar[String(year)] || month < 1 || month > 12 || day < 1 || day > daysInMonth(year, month)) return null;
            let serial = 0;
            for (const candidateYear of years) {
                if (candidateYear >= year) break;
                serial += (calendar[String(candidateYear)] || []).reduce((sum, days) => sum + Number(days || 0), 0);
            }
            for (let candidateMonth = 1; candidateMonth < month; candidateMonth++) serial += daysInMonth(year, candidateMonth);
            return serial + day - 1;
        };
        const bsToAd = value => {
            const targetSerial = serialDay(value), todaySerial = serialDay(config.today);
            if (targetSerial === null || todaySerial === null || !/^\d{4}-\d{2}-\d{2}$/.test(config.todayAd || '')) return '';
            const [year, month, day] = config.todayAd.split('-').map(Number);
            const converted = new Date(Date.UTC(year, month - 1, day));
            converted.setUTCDate(converted.getUTCDate() + targetSerial - todaySerial);
            return `${converted.getUTCFullYear()}-${pad(converted.getUTCMonth() + 1)}-${pad(converted.getUTCDate())}`;
        };
        const synchronizeAd = input => {
            const targetId = input.dataset.adTarget;
            if (!targetId) return;
            const target = document.getElementById(targetId);
            if (!target) return;
            const complete = normalized(input.value);
            const converted = complete ? bsToAd(complete) : '';
            target.value = converted;
            input.setCustomValidity(input.value.length === 10 && !converted ? 'Choose a valid Nepali calendar date.' : '');
            target.dispatchEvent(new Event('change', { bubbles: true }));
        };
        const boundary = (input, key) => {
            const raw = input.dataset[key] || '';
            return raw === 'today' ? normalized(config.today) : normalized(raw);
        };
        const allowed = (input, value) => {
            const min = boundary(input, 'minBs');
            const max = boundary(input, 'maxBs');
            return (!min || value >= min) && (!max || value <= max);
        };

        function firstWeekday(year, month) {
            const today = normalized(config.today);
            if (!today) return 0;
            const [todayYear, todayMonth, todayDay] = today.split('-').map(Number);
            let offset = -todayDay + 1;
            if (year > todayYear || (year === todayYear && month > todayMonth)) {
                let y = todayYear, m = todayMonth;
                while (y < year || (y === year && m < month)) {
                    offset += daysInMonth(y, m);
                    if (++m > 12) { m = 1; y++; }
                }
            } else if (year < todayYear || (year === todayYear && month < todayMonth)) {
                let y = year, m = month;
                while (y < todayYear || (y === todayYear && m < todayMonth)) {
                    offset -= daysInMonth(y, m);
                    if (++m > 12) { m = 1; y++; }
                }
            }
            return ((Number(config.todayDow || 0) + offset) % 7 + 7) % 7;
        }

        function close() {
            panel?.remove();
            panel = null;
            activeInput = null;
        }

        function position(input) {
            const rect = input.getBoundingClientRect();
            const top = Math.min(rect.bottom + 6, window.innerHeight - panel.offsetHeight - 12);
            const left = Math.min(rect.left, window.innerWidth - panel.offsetWidth - 12);
            panel.style.top = `${Math.max(12, top)}px`;
            panel.style.left = `${Math.max(12, left)}px`;
        }

        function select(input, value) {
            if (!allowed(input, value)) return;
            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            synchronizeAd(input);
            close();
        }

        function render(input, year, month) {
            panel?.remove();
            panel = document.createElement('div');
            panel.className = 'nepali-date-picker-panel';
            // A modal <dialog> lives in the browser's top layer, so ordinary
            // z-index values cannot place a body child above it. A popover is
            // also promoted to the top layer and therefore remains clickable
            // above academic-year and examination dialogs.
            panel.setAttribute('popover', 'manual');
            // Keep the popover inside the active modal's DOM subtree. Browsers
            // make everything outside a showModal() dialog inert, even when it
            // is painted in the top layer, which otherwise swallows date clicks.
            const activeDialog = input.closest('dialog[open]');
            (activeDialog || document.body).appendChild(panel);
            if (typeof panel.showPopover === 'function') {
                try { panel.showPopover(); } catch (error) { /* positioned fallback remains inside the dialog */ }
            }
            const selected = normalized(input.value);
            const today = normalized(config.today);
            const blanks = firstWeekday(year, month);
            const min = boundary(input, 'minBs');
            const max = boundary(input, 'maxBs');
            panel.innerHTML = `
                <div class="nepali-date-picker-head">
                    <select data-role="year">${years.map(y => `<option value="${y}" ${y === year ? 'selected' : ''}>${y}</option>`).join('')}</select>
                    <select data-role="month">${Array.from({length: 12}, (_, i) => i + 1).map(m => `<option value="${m}" ${m === month ? 'selected' : ''}>${months[String(m)] || m}</option>`).join('')}</select>
                </div>
                <div class="nepali-date-picker-today-row"><button type="button" data-today="${today}" ${today && allowed(input, today) ? '' : 'disabled'}>Today</button><span>${today ? `Today: ${today}` : ''}</span></div>
                ${(min || max) ? `<div class="nepali-date-picker-limit">${min ? `Min: ${min}` : ''}${min && max ? ' · ' : ''}${max ? `Max: ${max}` : ''}</div>` : ''}
                <div class="nepali-date-picker-week"><span>आ</span><span>सो</span><span>मं</span><span>बु</span><span>बि</span><span>शु</span><span>श</span></div>
                <div class="nepali-date-picker-days">
                    ${Array.from({length: blanks}, () => '<span></span>').join('')}
                    ${Array.from({length: daysInMonth(year, month)}, (_, i) => { const value = dateValue(year, month, i + 1); return `<button type="button" data-date="${value}" ${allowed(input, value) ? '' : 'disabled'} class="${selected === value ? 'selected' : ''} ${today === value ? 'today' : ''}">${i + 1}</button>`; }).join('')}
                </div>`;

            panel.querySelector('[data-role="month"]').addEventListener('change', event => render(input, year, Number(event.target.value)));
            panel.querySelector('[data-role="year"]').addEventListener('change', event => render(input, Number(event.target.value), month));
            panel.querySelectorAll('[data-date]').forEach(button => button.addEventListener('click', () => select(input, button.dataset.date)));
            panel.querySelector('[data-today]')?.addEventListener('click', event => select(input, event.currentTarget.dataset.today));
            position(input);
        }

        function open(input) {
            activeInput = input;
            const parsed = normalized(input.value || config.today);
            const [year, month] = parsed ? parsed.split('-').map(Number) : [years[years.length - 1], 1];
            render(input, calendar[String(year)] ? year : years[years.length - 1], month || 1);
        }

        document.querySelectorAll('.nepali-date-picker:not([data-nepali-picker])').forEach(input => {
            input.dataset.nepaliPicker = '1';
            input.setAttribute('autocomplete', 'off');
            const wrap = document.createElement('span');
            wrap.className = 'nepali-date-picker-wrap';
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(input);
            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'nepali-date-picker-trigger';
            trigger.title = 'Open Nepali calendar';
            trigger.setAttribute('aria-label', 'Open Nepali calendar');
            trigger.innerHTML = '<span aria-hidden="true">&#9638;</span>';
            wrap.appendChild(trigger);
            input.addEventListener('input', () => {
                const digits = String(input.value || '').replace(/\D/g, '').slice(0, 8);
                input.value = digits.length > 6 ? `${digits.slice(0, 4)}-${digits.slice(4, 6)}-${digits.slice(6)}` : (digits.length > 4 ? `${digits.slice(0, 4)}-${digits.slice(4)}` : digits);
                synchronizeAd(input);
            });
            input.addEventListener('blur', () => { const value = normalized(input.value); if (value) input.value = value; synchronizeAd(input); });
            input.addEventListener('focus', () => open(input));
            trigger.addEventListener('click', event => { event.preventDefault(); open(input); });
            synchronizeAd(input);
        });

        document.addEventListener('pointerdown', event => {
            if (panel && event.target !== activeInput && !panel.contains(event.target) && !event.target.closest('.nepali-date-picker-wrap')) close();
        });
        window.addEventListener('resize', () => activeInput && panel && position(activeInput));
        window.addEventListener('scroll', () => activeInput && panel && position(activeInput), true);
    }

    window.initNepaliDatePickers = initNepaliDatePickers;
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', initNepaliDatePickers) : initNepaliDatePickers();
})();
