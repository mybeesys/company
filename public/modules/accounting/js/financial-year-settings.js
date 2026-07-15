(function () {
    'use strict';

    const cfg = window.fySettingsConfig || {};
    const STORAGE_KEY = cfg.storageKey || 'bee_accounting_financial_years_v1';
    const msg = cfg.messages || {};

    let serverState = { years: [], firstSaved: false, locking_enabled: !!cfg.lockingEnabled };

    async function apiRequest(method, url, body) {
        const headers = {
            Accept: 'application/json',
            'X-CSRF-TOKEN': cfg.csrfToken || '',
            'X-Requested-With': 'XMLHttpRequest',
        };
        const opts = { method, headers };
        if (body !== undefined) {
            headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
        const res = await fetch(url, opts);
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.message || msg.apiError || 'Error');
        }
        return data;
    }

    async function reloadFromServer() {
        const data = await apiRequest('GET', cfg.api.index);
        serverState = {
            years: Array.isArray(data.years) ? data.years : [],
            firstSaved: !!data.first_saved,
            locking_enabled: !!data.locking_enabled,
        };
        normalizeFinancialData(serverState);
        const toggle = document.getElementById('fy-period-locking-toggle');
        if (toggle) {
            toggle.checked = serverState.locking_enabled;
        }
        return serverState;
    }

    const STATUS_LABELS = {
        open: msg.statusOpen || 'Open',
        closed: msg.statusClosed || 'Closed',
    };

    const STATUS_BADGE = {
        open: 'badge-fy-open',
        closed: 'badge-fy-closed',
    };

    function normalizeYearStatus(status) {
        if (status === 'closed' || status === 'closing') {
            return 'closed';
        }
        return 'open';
    }

    /** Display only: upcoming/closing → مغلقة */
    function normalizePeriodStatus(status) {
        if (status === 'closed' || status === 'closing' || status === 'upcoming') {
            return 'closed';
        }
        return 'open';
    }

    function normalizeFinancialData(state) {
        state.years.forEach((y) => {
            y.status = normalizeYearStatus(y.status);
            ensureYearPeriods(y);
            y.periods.forEach((p) => {
                p.status = normalizePeriodStatus(p.status);
            });
        });
    }

    let fpStart = null;
    let fpEnd = null;
    let fpEditYearStart = null;
    let fpEditYearEnd = null;

    function loadState() {
        return serverState;
    }

    function saveState(state) {
        serverState = state;
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
        const normalized = normalizeYearStatus(status);
        const label = STATUS_LABELS[normalized] || normalized;
        const cls = STATUS_BADGE[normalized] || 'badge-light';
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
                            ${buildYearActionsHtml(y)}
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

    function buildYearActionsHtml(year) {
        const hasActivity = !!year?.has_activity;
        const status = normalizeYearStatus(year?.status);

        const view = yearActionBtn('view', 'fa-eye', msg.actionViewYear || 'View');
        const report = yearActionBtn('report', 'fa-file-lines', msg.actionYearReport || 'Report');

        if (!hasActivity) {
            return (
                view +
                report +
                yearActionBtn('edit', 'fa-pen', msg.actionEditYear || 'Edit') +
                yearActionBtn('delete', 'fa-trash', msg.actionDeleteYear || 'Delete', 'btn-delete-year')
            );
        }

        // Has activity: hide delete, replace edit with year lock/unlock.
        if (status === 'closed') {
            return (
                view +
                report +
                yearActionBtn('year_open', 'fa-lock-open', msg.actionOpenYear || 'Open year', 'btn-year-open')
            );
        }

        return (
            view +
            report +
            yearActionBtn('year_close', 'fa-lock', msg.actionCloseYear || 'Close year', 'btn-year-close')
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
            } else if (action === 'report') {
                if (cfg.api?.reportYear) {
                    window.location.href = cfg.api.reportYear(yearId);
                }
            } else if (action === 'edit') {
                openYearEditModal(year);
            } else if (action === 'delete') {
                confirmDeleteYear(currentState, year);
            } else if (action === 'year_close') {
                confirmCloseYear(currentState, year);
            } else if (action === 'year_open') {
                confirmOpenYear(currentState, year);
            }
        });
    }

    function confirmCloseYear(state, year) {
        const label = year.description || `FY ${parseDate(year.end_date)?.getFullYear() || ''}`;
        const SwalApi = window.Swal;

        const runClose = async () => {
            try {
                await apiRequest('POST', cfg.api.closeYear(year.id));
                await reloadFromServer();
                refreshUi(loadState());
                toastr?.success(msg.yearClosedSuccess || msg.periodLockingSaved || 'Saved');
            } catch (err) {
                toastr?.error(err.message);
            }
        };

        if (SwalApi?.fire) {
            SwalApi.fire({
                title: msg.confirmCloseYearTitle || 'Close fiscal year?',
                text: (msg.confirmCloseYearText || 'Close «:name»').replace(':name', label),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: msg.confirmYesCloseYear || msg.confirmYesClose || 'Yes',
                cancelButtonText: msg.cancel,
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-light',
                },
            }).then((r) => {
                if (r.isConfirmed) {
                    runClose();
                }
            });
        } else if (confirm(msg.confirmCloseYearTitle || 'Close fiscal year?')) {
            runClose();
        }
    }

    function confirmOpenYear(state, year) {
        const label = year.description || `FY ${parseDate(year.end_date)?.getFullYear() || ''}`;
        const SwalApi = window.Swal;

        const runOpen = async () => {
            try {
                await apiRequest('POST', cfg.api.openYear(year.id));
                await reloadFromServer();
                refreshUi(loadState());
                toastr?.success(msg.yearOpenedSuccess || msg.periodLockingSaved || 'Saved');
            } catch (err) {
                toastr?.error(err.message);
            }
        };

        if (SwalApi?.fire) {
            SwalApi.fire({
                title: msg.confirmOpenYearTitle || 'Open fiscal year?',
                text: (msg.confirmOpenYearText || 'Open «:name»').replace(':name', label),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: msg.confirmYesOpenYear || msg.confirmYesOpen || 'Yes',
                cancelButtonText: msg.cancel,
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-light',
                },
            }).then((r) => {
                if (r.isConfirmed) {
                    runOpen();
                }
            });
        } else if (confirm(msg.confirmOpenYearTitle || 'Open fiscal year?')) {
            runOpen();
        }
    }

    function openYearEditModal(year) {
        document.getElementById('fy-edit-year-id').value = year.id;
        document.getElementById('fy-edit-year-description').value = year.description || '';
        document.getElementById('fy-edit-year-status').value = normalizeYearStatus(
            year.status
        );
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
        const runDelete = async () => {
            try {
                await apiRequest('DELETE', cfg.api.destroy(year.id));
                await reloadFromServer();
                const fresh = loadState();
                if (selectedYearIdMatches(year.id)) {
                    window.fySettingsApi?.closeYearDetail?.();
                }
                refreshUi(fresh);
                if (typeof toastr !== 'undefined') {
                    toastr.success(msg.yearDeletedSuccess);
                }
            } catch (err) {
                toastr?.error(err.message);
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
            year.status = normalizeYearStatus(
                document.getElementById('fy-edit-year-status').value
            );

            if (!year.description) {
                const endYear = parseDate(newEnd)?.getFullYear();
                year.description = endYear
                    ? cfg.locale === 'ar'
                        ? 'السنة المالية ' + endYear
                        : 'Fiscal year ' + endYear
                    : '';
            }

            apiRequest('PUT', cfg.api.update(yearId), {
                start_date: newStart,
                end_date: newEnd,
                description: year.description,
                status: year.status,
            })
                .then(async () => {
                    await reloadFromServer();
                    const fresh = loadState();
                    bootstrap.Modal.getInstance(document.getElementById('fyYearEditModal'))?.hide();
                    refreshUi(fresh);
                    if (selectedYearIdMatches(yearId)) {
                        window.fySettingsApi?.openYearDetail?.(yearId);
                    }
                    toastr?.success(msg.yearUpdatedSuccess);
                })
                .catch((err) => toastr?.error(err.message));
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

    function defaultPeriodStatus() {
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
                status: defaultPeriodStatus(),
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

    function resetAddYearForm(form) {
        if (!form) {
            return;
        }
        form.reset();
        clearFieldErrors(form);
        if (fpStart) {
            fpStart.clear();
        }
        if (fpEnd) {
            fpEnd.clear();
        }
        const statusInput = form.querySelector('#fy_status');
        if (statusInput) {
            statusInput.value = 'open';
        }
    }

    function setAddModalCopy(hasYears) {
        const titleEl = document.getElementById('fy-add-modal-title');
        const subtitleEl = document.getElementById('fy-add-modal-subtitle');
        const saveLabel = document.getElementById('fy-save-btn-label');

        if (titleEl) {
            titleEl.textContent = hasYears
                ? msg.sectionAddTitle || msg.addYear || 'Add fiscal year'
                : msg.sectionSetupTitle || titleEl.textContent;
        }
        if (subtitleEl) {
            subtitleEl.textContent = hasYears
                ? msg.sectionAddSubtitle || subtitleEl.textContent
                : msg.sectionSetupSubtitle || subtitleEl.textContent;
        }
        if (saveLabel) {
            saveLabel.textContent = hasYears
                ? msg.addYear || msg.saveFirstYear || 'Add fiscal year'
                : msg.saveFirstYear || saveLabel.textContent;
        }
    }

    function setAddYearFieldsReadonly(readonly) {
        ['#fy_start_date', '#fy_end_date'].forEach((sel) => {
            const el = document.querySelector(sel);
            if (el) {
                el.readOnly = readonly;
                el.classList.toggle('bg-light', readonly);
            }
        });
        const hint = document.getElementById('fy-auto-next-hint');
        if (hint) {
            hint.classList.toggle('d-none', !readonly);
        }
    }

    async function prefetchNextYearDates(form) {
        if (!cfg.api?.nextRange) {
            return;
        }
        try {
            const data = await apiRequest('GET', cfg.api.nextRange);
            const startInput = form.querySelector('#fy_start_date');
            const endInput = form.querySelector('#fy_end_date');
            const descInput = form.querySelector('[name="description"]');
            if (startInput) {
                startInput.value = data.start_date;
            }
            if (endInput) {
                endInput.value = data.end_date;
            }
            if (descInput && data.name) {
                descInput.value = data.name;
            }
            if (fpStart && data.start_date) {
                fpStart.setDate(data.start_date, false);
            }
            if (fpEnd && data.end_date) {
                fpEnd.setDate(data.end_date, false);
            }
            setAddYearFieldsReadonly(true);
        } catch (err) {
            setAddYearFieldsReadonly(false);
            toastr?.error(err.message);
        }
    }

    function openAddYearModal(state) {
        const form = document.getElementById('fy-add-year-form');
        if (!form) {
            return;
        }
        const hasYears = state.years.length > 0;
        setAddModalCopy(hasYears);
        resetAddYearForm(form);
        setAddYearFieldsReadonly(false);
        initAddPickers();
        initAddModalTooltips();
        if (hasYears) {
            prefetchNextYearDates(form);
        }
        const modalEl = document.getElementById('fyYearAddModal');
        if (modalEl && window.bootstrap?.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, focus: true }).show();
        }
    }

    function isFinancialYearTabActive() {
        const pane = document.getElementById('financial_year_settings_tab');
        return pane?.classList.contains('active') && pane?.classList.contains('show');
    }

    function maybeAutoOpenAddYearModal(state) {
        if (!state.years.length && isFinancialYearTabActive()) {
            openAddYearModal(state);
        }
    }

    function bindFinancialYearTabAutoOpen(state) {
        document
            .querySelector('#accountingSettingsTabs a[href="#financial_year_settings_tab"]')
            ?.addEventListener('shown.bs.tab', function () {
                maybeAutoOpenAddYearModal(loadState());
            });
    }

    function initAddModalTooltips() {
        document
            .querySelectorAll('#fyYearAddModal [data-bs-toggle="tooltip"]')
            .forEach((el) => {
                const existing = bootstrap.Tooltip.getInstance(el);
                if (existing) {
                    existing.dispose();
                }
                new bootstrap.Tooltip(el);
            });
    }

    function bindAddYearButtons(state) {
        ['fy-btn-add-year', 'fy-btn-add-year-current'].forEach(
            (id) => {
                document.getElementById(id)?.addEventListener('click', function () {
                    openAddYearModal(state);
                });
            }
        );

        const modalEl = document.getElementById('fyYearAddModal');
        modalEl?.addEventListener('shown.bs.modal', function () {
            fpStart?.redraw?.();
            fpEnd?.redraw?.();
        });
    }

    function showTableLoading(show) {
        const el = document.getElementById('fy-table-loading');
        if (el) {
            el.classList.toggle('is-active', show);
        }
    }

    function updateAddYearButtonsVisibility(state) {
        const hasYears = state.years.length > 0;
        document.getElementById('fy-btn-add-year')?.classList.toggle('d-none', !hasYears);
        document.getElementById('fy-btn-add-year-current')?.classList.toggle('d-none', hasYears);
    }

    function refreshUi(state) {
        renderDashboard(state);
        renderTable(state);
        updateAddYearButtonsVisibility(state);
        state.firstSaved = state.years.length > 0;
    }

    function flatpickrBaseOpts() {
        const opts = {
            dateFormat: 'Y-m-d',
            allowInput: true,
            disableMobile: true,
        };
        if (cfg.locale === 'ar' && window.flatpickr?.l10ns?.ar) {
            opts.locale = window.flatpickr.l10ns.ar;
        }
        return opts;
    }

    function initAddPickers() {
        if (typeof flatpickr === 'undefined') {
            return;
        }
        const opts = flatpickrBaseOpts();

        if (!fpStart) {
            fpStart = flatpickr('#fy_start_date', {
                ...opts,
                onChange: function (selectedDates) {
                    if (fpEnd && selectedDates[0]) {
                        fpEnd.set('minDate', selectedDates[0]);
                    }
                },
            });
        }

        if (!fpEnd) {
            fpEnd = flatpickr('#fy_end_date', {
                ...opts,
                onChange: function (selectedDates) {
                    if (fpStart && selectedDates[0]) {
                        fpStart.set('maxDate', selectedDates[0]);
                    }
                },
            });
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
        const form = document.getElementById('fy-add-year-form');
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

            const hasYears = state.years.length > 0;
            apiRequest('POST', cfg.api.store, {
                start_date: payload.start_date,
                end_date: payload.end_date,
                description: payload.description,
                status: payload.status,
                auto_next: hasYears,
            })
                .then(async () => {
                    await reloadFromServer();
                    const fresh = loadState();
                    loading?.classList.remove('is-active');
                    saveBtn?.removeAttribute('disabled');
                    resetAddYearForm(form);
                    bootstrap.Modal.getInstance(document.getElementById('fyYearAddModal'))?.hide();
                    showTableLoading(true);
                    setTimeout(function () {
                        showTableLoading(false);
                        refreshUi(fresh);
                    }, 300);
                    toastr?.success(msg.saveSuccess);
                })
                .catch((err) => {
                    loading?.classList.remove('is-active');
                    saveBtn?.removeAttribute('disabled');
                    toastr?.error(err.message);
                });
        });

        ['#fy_start_date', '#fy_end_date', '#fy_status'].forEach((sel) => {
            const input = form.querySelector(sel);
            input?.addEventListener('input', () => input.classList.remove('is-invalid'));
            input?.addEventListener('change', () => input.classList.remove('is-invalid'));
        });
    }

    function bindLockingToggle() {
        document.getElementById('fy-period-locking-toggle')?.addEventListener('change', function () {
            apiRequest('PUT', cfg.api.locking, { enabled: this.checked })
                .then((data) => {
                    serverState.locking_enabled = !!data.locking_enabled;
                    toastr?.success(msg.periodLockingSaved);
                })
                .catch((err) => {
                    this.checked = !this.checked;
                    toastr?.error(err.message);
                });
        });
    }

    document.addEventListener('DOMContentLoaded', async function () {
        try {
            await reloadFromServer();
        } catch (err) {
            console.warn('Financial years API:', err);
            serverState = { years: [], firstSaved: false, locking_enabled: !!cfg.lockingEnabled };
        }

        const state = loadState();

        initTooltips();
        bindAddYearButtons(state);
        bindForm(state);
        bindYearEditForm(state);
        bindLockingToggle();
        bindFinancialYearTabAutoOpen(state);
        refreshUi(state);

        maybeAutoOpenAddYearModal(state);

        window.fySettingsApi = {
            loadState,
            saveState,
            reloadFromServer,
            apiRequest,
            parseDate,
            formatDisplayDate,
            statusBadgeHtml,
            escapeHtml,
            ensureYearPeriods,
            refreshUi: () => refreshUi(loadState()),
            getState: () => loadState(),
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
