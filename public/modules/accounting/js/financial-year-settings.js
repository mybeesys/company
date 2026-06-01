(function () {
    'use strict';

    const cfg = window.fySettingsConfig || {};
    const STORAGE_KEY = cfg.storageKey || 'bee_accounting_financial_years_v1';
    const msg = cfg.messages || {};

    const STATUS_LABELS = {
        open: msg.statusOpen || 'Open',
        closed: msg.statusClosed || 'Closed',
        upcoming: msg.statusUpcoming || 'Upcoming',
    };

    const STATUS_BADGE = {
        open: 'badge-fy-open',
        closed: 'badge-fy-closed',
        upcoming: 'badge-fy-upcoming',
    };

    let fpStart = null;
    let fpEnd = null;
    let fpEditYearStart = null;
    let fpEditYearEnd = null;

    function loadState() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) {
                return { years: [], firstSaved: false };
            }
            const parsed = JSON.parse(raw);
            return {
                years: Array.isArray(parsed.years) ? parsed.years : [],
                firstSaved: !!parsed.firstSaved,
            };
        } catch (e) {
            return { years: [], firstSaved: false };
        }
    }

    function saveState(state) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    }

    function parseDate(str) {
        if (!str || typeof str !== 'string') {
            return null;
        }
        const parts = str.trim().split('-');
        if (parts.length !== 3) {
            return null;
        }
        const y = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10) - 1;
        const d = parseInt(parts[2], 10);
        const date = new Date(y, m, d);
        if (
            date.getFullYear() !== y ||
            date.getMonth() !== m ||
            date.getDate() !== d
        ) {
            return null;
        }
        return date;
    }

    function formatDisplayDate(str) {
        const d = parseDate(str);
        if (!d) {
            return str || msg.dash || '—';
        }
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return cfg.locale === 'ar' ? `${day}/${month}/${year}` : `${year}-${month}-${day}`;
    }

    function daysInclusive(startStr, endStr) {
        const s = parseDate(startStr);
        const e = parseDate(endStr);
        if (!s || !e) {
            return 0;
        }
        const diff = Math.round((e - s) / (1000 * 60 * 60 * 24)) + 1;
        return Math.max(0, diff);
    }

    function monthsSpan(startStr, endStr) {
        const s = parseDate(startStr);
        const e = parseDate(endStr);
        if (!s || !e) {
            return 0;
        }
        let months =
            (e.getFullYear() - s.getFullYear()) * 12 +
            (e.getMonth() - s.getMonth());
        if (e.getDate() >= s.getDate()) {
            months += 1;
        }
        return Math.max(1, months);
    }

    function durationLabel(startStr, endStr) {
        const months = monthsSpan(startStr, endStr);
        const days = daysInclusive(startStr, endStr);
        const mUnit = msg.monthsUnit || 'month(s)';
        const dUnit = msg.daysUnit || 'day(s)';
        return `${months} ${mUnit} · ${days} ${dUnit}`;
    }

    function statusBadgeHtml(status) {
        const label = STATUS_LABELS[status] || status;
        const cls = STATUS_BADGE[status] || 'badge-light';
        return `<span class="badge ${cls} fw-semibold px-3 py-2">${label}</span>`;
    }

    function getCurrentYear(state) {
        if (!state.years.length) {
            return null;
        }
        const open = state.years.find((y) => y.status === 'open');
        if (open) {
            return open;
        }
        return state.years[state.years.length - 1];
    }

    function setFieldError(input, message) {
        if (!input) {
            return;
        }
        input.classList.add('is-invalid');
        const feedback = input.parentElement.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.textContent = message;
        }
    }

    function clearFieldErrors(form) {
        form.querySelectorAll('.is-invalid').forEach((el) => {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback').forEach((el) => {
            el.textContent = '';
        });
    }

    function validateForm(form, state) {
        clearFieldErrors(form);
        let valid = true;

        if (state.firstSaved) {
            if (typeof toastr !== 'undefined') {
                toastr.warning(msg.firstYearExists);
            }
            return false;
        }

        const startInput = form.querySelector('#fy_start_date');
        const endInput = form.querySelector('#fy_end_date');
        const statusInput = form.querySelector('#fy_status');

        const startVal = startInput.value.trim();
        const endVal = endInput.value.trim();
        const statusVal = statusInput.value;

        if (!startVal) {
            setFieldError(startInput, msg.required);
            valid = false;
        } else if (!parseDate(startVal)) {
            setFieldError(startInput, msg.invalidDate);
            valid = false;
        }

        if (!endVal) {
            setFieldError(endInput, msg.required);
            valid = false;
        } else if (!parseDate(endVal)) {
            setFieldError(endInput, msg.invalidDate);
            valid = false;
        }

        if (!statusVal) {
            setFieldError(statusInput, msg.required);
            valid = false;
        }

        if (valid && startVal && endVal) {
            const s = parseDate(startVal);
            const e = parseDate(endVal);
            if (e < s) {
                setFieldError(endInput, msg.endBeforeStart);
                valid = false;
            }
        }

        return valid;
    }

    function renderDashboard(state) {
        const emptyEl = document.getElementById('fy-current-empty');
        const cardsEl = document.getElementById('fy-current-cards');
        const current = getCurrentYear(state);

        if (!current) {
            emptyEl?.classList.remove('d-none');
            cardsEl?.classList.add('d-none');
            return;
        }

        emptyEl?.classList.add('d-none');
        cardsEl?.classList.remove('d-none');

        const label =
            current.description ||
            `FY ${parseDate(current.end_date)?.getFullYear() || ''}`;

        document.getElementById('fy-stat-year').textContent = label;
        document.getElementById('fy-stat-start').textContent = formatDisplayDate(
            current.start_date
        );
        document.getElementById('fy-stat-end').textContent = formatDisplayDate(
            current.end_date
        );
        document.getElementById('fy-stat-months').textContent =
            monthsSpan(current.start_date, current.end_date) +
            ' ' +
            (msg.monthsUnit || '');
        document.getElementById('fy-stat-status').innerHTML = statusBadgeHtml(
            current.status
        );
    }

    function renderTable(state) {
        const emptyEl = document.getElementById('fy-history-empty');
        const wrapEl = document.getElementById('fy-history-table-wrap');
        const tbody = document.getElementById('fy-history-tbody');

        if (!state.years.length) {
            emptyEl?.classList.remove('d-none');
            wrapEl?.classList.add('d-none');
            if (tbody) {
                tbody.innerHTML = '';
            }
            return;
        }

        emptyEl?.classList.add('d-none');
        wrapEl?.classList.remove('d-none');

        const rows = [...state.years]
            .sort((a, b) => (a.start_date < b.start_date ? 1 : -1))
            .map((y) => {
                const label =
                    y.description ||
                    `FY ${parseDate(y.end_date)?.getFullYear() || ''}`;
                return `<tr data-year-id="${escapeHtml(y.id)}">
                    <td class="fw-semibold text-gray-800">${escapeHtml(label)}</td>
                    <td class="fy-date-num">${formatDisplayDate(y.start_date)}</td>
                    <td class="fy-date-num">${formatDisplayDate(y.end_date)}</td>
                    <td class="text-muted">${durationLabel(y.start_date, y.end_date)}</td>
                    <td class="text-center">${statusBadgeHtml(y.status)}</td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1 flex-wrap justify-content-end fy-year-actions">
                            ${buildYearActionsHtml(y.id)}
                        </div>
                    </td>
                </tr>`;
            })
            .join('');

        tbody.innerHTML = rows;
        bindYearRowActions(state);
        initYearActionTooltips(tbody);
    }

    function yearActionBtn(action, icon, title, extraClass) {
        const t = escapeAttr(title);
        return `<button type="button" class="fy-action-btn ${extraClass || ''}" data-year-action="${action}" title="${t}" data-bs-toggle="tooltip">
            <i class="fas ${icon} fs-7"></i>
        </button>`;
    }

    function escapeAttr(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    function buildYearActionsHtml() {
        return (
            yearActionBtn('view', 'fa-eye', msg.actionViewYear || 'View') +
            yearActionBtn('edit', 'fa-pen', msg.actionEditYear || 'Edit') +
            yearActionBtn('delete', 'fa-trash', msg.actionDeleteYear || 'Delete', 'btn-delete-year')
        );
    }

    function initYearActionTooltips(root) {
        root?.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            const existing = bootstrap.Tooltip.getInstance(el);
            if (existing) {
                existing.dispose();
            }
            new bootstrap.Tooltip(el);
        });
    }

    function findYearById(state, yearId) {
        if (!state?.years || yearId == null) {
            return null;
        }
        const id = String(yearId);
        return state.years.find((y) => String(y.id) === id);
    }

    function openYearDetailView(yearId) {
        const id = String(yearId);
        if (typeof window.FyFiscalPeriods?.showDetailView === 'function') {
            window.FyFiscalPeriods.showDetailView(id);
            return;
        }
        if (typeof window.fySettingsApi?.openYearDetail === 'function') {
            window.fySettingsApi.openYearDetail(id);
            return;
        }
        console.warn('Year detail view is not ready yet');
    }

    function bindYearRowActions(state) {
        const tbody = document.getElementById('fy-history-tbody');
        if (!tbody || tbody.dataset.fyDelegated === '1') {
            return;
        }
        tbody.dataset.fyDelegated = '1';
        tbody.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-year-action]');
            if (!btn) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            const row = btn.closest('tr');
            const yearId = row?.dataset.yearId;
            const currentState = window.fySettingsApi?.getState?.() || state;
            const year = findYearById(currentState, yearId);
            if (!year) {
                return;
            }
            const action = btn.dataset.yearAction;
            if (action === 'view') {
                openYearDetailView(yearId);
            } else if (action === 'edit') {
                openYearEditModal(year);
            } else if (action === 'delete') {
                confirmDeleteYear(currentState, year);
            }
        });
    }

    function openYearEditModal(year) {
        document.getElementById('fy-edit-year-id').value = year.id;
        document.getElementById('fy-edit-year-description').value = year.description || '';
        document.getElementById('fy-edit-year-status').value = year.status || 'open';
        document.getElementById('fy-edit-year-start').value = year.start_date;
        document.getElementById('fy-edit-year-end').value = year.end_date;
        initEditYearPickers();
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('fyYearEditModal')
        ).show();
    }

    function initEditYearPickers() {
        const opts = {
            dateFormat: 'Y-m-d',
            allowInput: true,
            disableMobile: true,
        };
        if (cfg.locale === 'ar' && window.flatpickr?.l10ns?.ar) {
            opts.locale = window.flatpickr.l10ns.ar;
        }
        if (!fpEditYearStart) {
            fpEditYearStart = flatpickr('#fy-edit-year-start', {
                ...opts,
                onChange: function (selectedDates) {
                    if (fpEditYearEnd && selectedDates[0]) {
                        fpEditYearEnd.set('minDate', selectedDates[0]);
                    }
                },
            });
            fpEditYearEnd = flatpickr('#fy-edit-year-end', {
                ...opts,
                onChange: function (selectedDates) {
                    if (fpEditYearStart && selectedDates[0]) {
                        fpEditYearStart.set('maxDate', selectedDates[0]);
                    }
                },
            });
        }
    }

    function validateYearEditForm() {
        const form = document.getElementById('fy-year-edit-form');
        const startInput = document.getElementById('fy-edit-year-start');
        const endInput = document.getElementById('fy-edit-year-end');
        const statusInput = document.getElementById('fy-edit-year-status');
        let valid = true;

        [startInput, endInput].forEach((input) => {
            input.classList.remove('is-invalid');
            const fb = input.parentElement.querySelector('.invalid-feedback');
            if (fb) {
                fb.textContent = '';
            }
        });

        const startVal = startInput.value.trim();
        const endVal = endInput.value.trim();

        if (!startVal) {
            startInput.classList.add('is-invalid');
            startInput.parentElement.querySelector('.invalid-feedback').textContent =
                msg.required;
            valid = false;
        } else if (!parseDate(startVal)) {
            startInput.classList.add('is-invalid');
            startInput.parentElement.querySelector('.invalid-feedback').textContent =
                msg.invalidDate;
            valid = false;
        }

        if (!endVal) {
            endInput.classList.add('is-invalid');
            endInput.parentElement.querySelector('.invalid-feedback').textContent =
                msg.required;
            valid = false;
        } else if (!parseDate(endVal)) {
            endInput.classList.add('is-invalid');
            endInput.parentElement.querySelector('.invalid-feedback').textContent =
                msg.invalidDate;
            valid = false;
        }

        if (valid && startVal && endVal) {
            const s = parseDate(startVal);
            const e = parseDate(endVal);
            if (e < s) {
                endInput.classList.add('is-invalid');
                endInput.parentElement.querySelector('.invalid-feedback').textContent =
                    msg.endBeforeStart;
                valid = false;
            }
        }

        if (!statusInput.value) {
            valid = false;
        }

        return valid;
    }

    function confirmDeleteYear(state, year) {
        const label =
            year.description ||
            `FY ${parseDate(year.end_date)?.getFullYear() || ''}`;
        const SwalApi = window.Swal;
        const runDelete = () => {
            state.years = state.years.filter((y) => y.id !== year.id);
            state.firstSaved = state.years.length > 0;
            saveState(state);
            if (selectedYearIdMatches(year.id)) {
                window.fySettingsApi?.closeYearDetail?.();
            }
            refreshUi(state);
            if (typeof toastr !== 'undefined') {
                toastr.success(msg.yearDeletedSuccess);
            }
        };

        if (SwalApi?.fire) {
            SwalApi.fire({
                title: msg.confirmDeleteYearTitle,
                text: (msg.confirmDeleteYearText || '').replace(':name', label),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: msg.confirmYesDelete,
                cancelButtonText: msg.cancel,
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-light',
                },
            }).then((r) => {
                if (r.isConfirmed) {
                    runDelete();
                }
            });
        } else if (confirm(msg.confirmDeleteYearTitle)) {
            runDelete();
        }
    }

    function selectedYearIdMatches(yearId) {
        const urlYear = new URL(window.location.href).searchParams.get('year');
        return urlYear === yearId;
    }

    function bindYearEditForm(state) {
        document.getElementById('fy-year-edit-form')?.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!validateYearEditForm()) {
                return;
            }
            const yearId = document.getElementById('fy-edit-year-id').value;
            const year = findYearById(state, yearId);
            if (!year) {
                return;
            }

            const newStart = document.getElementById('fy-edit-year-start').value.trim();
            const newEnd = document.getElementById('fy-edit-year-end').value.trim();
            const datesChanged =
                year.start_date !== newStart || year.end_date !== newEnd;

            year.start_date = newStart;
            year.end_date = newEnd;
            year.description = document
                .getElementById('fy-edit-year-description')
                .value.trim();
            year.status = document.getElementById('fy-edit-year-status').value;

            if (!year.description) {
                const endYear = parseDate(newEnd)?.getFullYear();
                year.description = endYear
                    ? cfg.locale === 'ar'
                        ? 'السنة المالية ' + endYear
                        : 'Fiscal year ' + endYear
                    : '';
            }

            if (datesChanged) {
                year.periods = generatePeriodsForYear(newStart, newEnd);
            }

            saveState(state);
            bootstrap.Modal.getInstance(document.getElementById('fyYearEditModal'))?.hide();
            refreshUi(state);
            if (selectedYearIdMatches(yearId)) {
                window.fySettingsApi?.openYearDetail?.(yearId);
            }
            if (typeof toastr !== 'undefined') {
                toastr.success(msg.yearUpdatedSuccess);
            }
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function toIsoDate(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

    function startOfDay(d) {
        return new Date(d.getFullYear(), d.getMonth(), d.getDate());
    }

    function periodNameForDate(d) {
        if (cfg.locale === 'ar') {
            return d.toLocaleDateString('ar-SA', { month: 'long', year: 'numeric' });
        }
        return d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }

    function inferPeriodStatus(periodStart, periodEnd) {
        const today = startOfDay(new Date());
        const s = startOfDay(periodStart);
        const e = startOfDay(periodEnd);
        if (s > today) {
            return 'upcoming';
        }
        if (e < today) {
            return 'closed';
        }
        return 'open';
    }

    function generatePeriodsForYear(startStr, endStr) {
        const start = parseDate(startStr);
        const end = parseDate(endStr);
        if (!start || !end) {
            return [];
        }
        const periods = [];
        let cursor = new Date(start);
        let index = 0;
        while (cursor <= end) {
            const periodStart = new Date(cursor);
            let periodEnd = new Date(periodStart.getFullYear(), periodStart.getMonth() + 1, 0);
            if (periodEnd > end) {
                periodEnd = new Date(end);
            }
            periods.push({
                id: 'p_' + index + '_' + toIsoDate(periodStart).replace(/-/g, ''),
                name: periodNameForDate(periodStart),
                start_date: toIsoDate(periodStart),
                end_date: toIsoDate(periodEnd),
                status: inferPeriodStatus(periodStart, periodEnd),
            });
            index += 1;
            cursor = new Date(periodEnd);
            cursor.setDate(cursor.getDate() + 1);
        }
        return periods;
    }

    function ensureYearPeriods(year) {
        if (!year.periods || !year.periods.length) {
            year.periods = generatePeriodsForYear(year.start_date, year.end_date);
        }
        return year.periods;
    }

    function updateSetupUi(state) {
        const form = document.getElementById('fy-first-year-form');
        const notice = document.getElementById('fy-auto-years-notice');
        const saveBtn = document.getElementById('fy-save-btn');
        const setupSection = document.getElementById('fy-setup-section');

        state.firstSaved = state.years.length > 0;

        if (state.firstSaved) {
            form?.classList.add('d-none');
            notice?.classList.remove('d-none');
            saveBtn?.setAttribute('disabled', 'disabled');
            setupSection?.classList.add('d-none');
        } else {
            form?.classList.remove('d-none');
            notice?.classList.add('d-none');
            saveBtn?.removeAttribute('disabled');
            setupSection?.classList.remove('d-none');
        }
    }

    function showTableLoading(show) {
        const el = document.getElementById('fy-table-loading');
        if (el) {
            el.classList.toggle('is-active', show);
        }
    }

    function refreshUi(state) {
        renderDashboard(state);
        renderTable(state);
        updateSetupUi(state);
    }

    function initPickers() {
        const opts = {
            dateFormat: 'Y-m-d',
            allowInput: true,
            disableMobile: true,
        };
        if (cfg.locale === 'ar' && window.flatpickr?.l10ns?.ar) {
            opts.locale = window.flatpickr.l10ns.ar;
        }

        fpStart = flatpickr('#fy_start_date', {
            ...opts,
            onChange: function (selectedDates, dateStr) {
                if (fpEnd && selectedDates[0]) {
                    fpEnd.set('minDate', selectedDates[0]);
                }
            },
        });

        fpEnd = flatpickr('#fy_end_date', {
            ...opts,
            onChange: function (selectedDates) {
                if (fpStart && selectedDates[0]) {
                    fpStart.set('maxDate', selectedDates[0]);
                }
            },
        });

        if (fpStart?.selectedDates?.[0]) {
            fpEnd.set('minDate', fpStart.selectedDates[0]);
        }
    }

    function initTooltips() {
        const triggers = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        triggers.forEach((el) => {
            if (window.bootstrap?.Tooltip) {
                new bootstrap.Tooltip(el);
            }
        });
    }

    function bindForm(state) {
        const form = document.getElementById('fy-first-year-form');
        if (!form) {
            return;
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!validateForm(form, state)) {
                return;
            }

            const loading = document.getElementById('fy-form-loading');
            const saveBtn = document.getElementById('fy-save-btn');
            loading?.classList.add('is-active');
            saveBtn?.setAttribute('disabled', 'disabled');

            const payload = {
                id: 'fy_' + Date.now(),
                start_date: form.start_date.value.trim(),
                end_date: form.end_date.value.trim(),
                description: form.description.value.trim(),
                status: form.status.value,
                created_at: new Date().toISOString(),
            };

            if (!payload.description) {
                const endYear = parseDate(payload.end_date)?.getFullYear();
                payload.description = endYear
                    ? (cfg.locale === 'ar'
                          ? 'السنة المالية ' + endYear
                          : 'Fiscal year ' + endYear)
                    : '';
            }

            setTimeout(function () {
                payload.periods = generatePeriodsForYear(
                    payload.start_date,
                    payload.end_date
                );
                state.years.push(payload);
                state.firstSaved = true;
                saveState(state);

                loading?.classList.remove('is-active');
                saveBtn?.removeAttribute('disabled');

                showTableLoading(true);
                setTimeout(function () {
                    showTableLoading(false);
                    refreshUi(state);
                }, 400);

                if (typeof toastr !== 'undefined') {
                    toastr.success(msg.saveSuccess);
                }
            }, 700);
        });

        ['#fy_start_date', '#fy_end_date', '#fy_status'].forEach((sel) => {
            const input = form.querySelector(sel);
            input?.addEventListener('input', () => input.classList.remove('is-invalid'));
            input?.addEventListener('change', () => input.classList.remove('is-invalid'));
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const state = loadState();

        state.years.forEach((y) => ensureYearPeriods(y));
        saveState(state);

        if (typeof flatpickr !== 'undefined') {
            initPickers();
        } else {
            console.warn('flatpickr not loaded — date pickers disabled on financial year form');
        }

        initTooltips();
        bindForm(state);
        bindYearEditForm(state);
        refreshUi(state);

        window.fySettingsApi = {
            loadState,
            saveState,
            parseDate,
            formatDisplayDate,
            statusBadgeHtml,
            escapeHtml,
            ensureYearPeriods,
            refreshUi: () => refreshUi(state),
            getState: () => state,
            openYearDetail: openYearDetailView,
            closeYearDetail: () => window.FyFiscalPeriods?.showListView?.(),
            cfg,
            msg,
        };

        if (typeof window.FyFiscalPeriods?.showDetailView === 'function') {
            window.fySettingsApi.openYearDetail = window.FyFiscalPeriods.showDetailView;
            window.fySettingsApi.closeYearDetail = window.FyFiscalPeriods.showListView;
        }
    });
})();
