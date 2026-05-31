(function () {
    'use strict';

    const PAGE_SIZE = 10;
    let selectedYearId = null;
    let periodsSort = { field: 'start_date', dir: 'asc' };
    let periodsPage = 1;
    let periodsSearch = '';
    let editFpStart = null;
    let editFpEnd = null;

    function api() {
        return window.fySettingsApi;
    }

    function msg() {
        return api()?.msg || {};
    }

    function periodStatusLabels() {
        const m = msg();
        return {
            open: m.periodOpen || 'Open',
            closed: m.periodClosed || 'Closed',
            closing: m.periodClosing || 'Closing',
            upcoming: m.periodUpcoming || 'Upcoming',
        };
    }

    function periodStatusBadge(status) {
        const labels = periodStatusLabels();
        const map = {
            open: 'badge-fy-period-open',
            closed: 'badge-fy-period-closed',
            closing: 'badge-fy-period-closing',
            upcoming: 'badge-fy-period-upcoming',
        };
        const label = labels[status] || status;
        const cls = map[status] || 'badge-light';
        return `<span class="badge ${cls} fw-semibold px-3 py-2">${label}</span>`;
    }

    function findYear(state, yearId) {
        return state.years.find((y) => y.id === yearId);
    }

    function findPeriod(year, periodId) {
        return year?.periods?.find((p) => p.id === periodId);
    }

    function showListView() {
        document.getElementById('fy-years-list-view')?.classList.remove('d-none');
        document.getElementById('fy-year-detail-view')?.classList.add('d-none');
        selectedYearId = null;
        if (history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('year');
            history.replaceState(null, '', url.toString());
        }
    }

    function showDetailView(yearId) {
        document.getElementById('fy-years-list-view')?.classList.add('d-none');
        document.getElementById('fy-year-detail-view')?.classList.remove('d-none');
        selectedYearId = yearId;
        if (history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('year', yearId);
            history.replaceState(null, '', url.toString());
        }
        renderYearDetail(yearId);
    }

    function renderYearDetail(yearId) {
        const a = api();
        if (!a) {
            return;
        }
        const state = a.getState();
        const year = findYear(state, yearId);
        if (!year) {
            showListView();
            return;
        }

        a.ensureYearPeriods(year);
        a.saveState(state);

        const label =
            year.description ||
            `FY ${a.parseDate(year.end_date)?.getFullYear() || ''}`;

        document.getElementById('fy-detail-name').textContent = label;
        document.getElementById('fy-detail-start').textContent = a.formatDisplayDate(
            year.start_date
        );
        document.getElementById('fy-detail-end').textContent = a.formatDisplayDate(
            year.end_date
        );
        document.getElementById('fy-detail-status').innerHTML = a.statusBadgeHtml(
            year.status
        );

        periodsPage = 1;
        renderPeriodsTable(year);
        initDetailTooltips();
    }

    function filterPeriods(periods) {
        const q = periodsSearch.trim().toLowerCase();
        if (!q) {
            return periods;
        }
        return periods.filter(
            (p) =>
                (p.name || '').toLowerCase().includes(q) ||
                (p.start_date || '').includes(q) ||
                (p.end_date || '').includes(q)
        );
    }

    function sortPeriods(periods) {
        const field = periodsSort.field;
        const dir = periodsSort.dir === 'desc' ? -1 : 1;
        return [...periods].sort((x, y) => {
            let vx = x[field];
            let vy = y[field];
            if (field === 'status') {
                const order = { open: 1, closing: 2, upcoming: 3, closed: 4 };
                vx = order[vx] || 9;
                vy = order[vy] || 9;
            }
            if (vx < vy) {
                return -1 * dir;
            }
            if (vx > vy) {
                return 1 * dir;
            }
            return 0;
        });
    }

    function renderPeriodsTable(year) {
        const a = api();
        const tbody = document.getElementById('fy-periods-tbody');
        const emptyEl = document.getElementById('fy-periods-empty');
        const tableWrap = document.querySelector('.fy-periods-table-wrap');
        const m = msg();

        const all = year.periods || [];
        const filtered = sortPeriods(filterPeriods(all));
        const total = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (periodsPage > totalPages) {
            periodsPage = totalPages;
        }
        const startIdx = (periodsPage - 1) * PAGE_SIZE;
        const pageItems = filtered.slice(startIdx, startIdx + PAGE_SIZE);

        document.querySelectorAll('.fy-periods-thead .fy-sortable').forEach((th) => {
            th.classList.toggle('fy-sort-active', th.dataset.sort === periodsSort.field);
            const icon = th.querySelector('.fy-sort-icon');
            if (icon) {
                icon.className =
                    'fas fy-sort-icon ' +
                    (periodsSort.field === th.dataset.sort
                        ? periodsSort.dir === 'asc'
                            ? 'fa-sort-up'
                            : 'fa-sort-down'
                        : 'fa-sort');
            }
        });

        if (!total) {
            tbody.innerHTML = '';
            emptyEl?.classList.remove('d-none');
            tableWrap?.classList.add('d-none');
        } else {
            emptyEl?.classList.add('d-none');
            tableWrap?.classList.remove('d-none');
            tbody.innerHTML = pageItems
                .map((p) => {
                    const actions = buildPeriodActions(p);
                    return `<tr data-period-id="${a.escapeHtml(p.id)}">
                        <td class="fw-semibold text-gray-800">${a.escapeHtml(p.name)}</td>
                        <td class="fy-date-num">${a.formatDisplayDate(p.start_date)}</td>
                        <td class="fy-date-num">${a.formatDisplayDate(p.end_date)}</td>
                        <td class="text-center">${periodStatusBadge(p.status)}</td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1 flex-wrap justify-content-end">${actions}</div>
                        </td>
                    </tr>`;
                })
                .join('');
        }

        const showing = document.getElementById('fy-periods-showing');
        if (total) {
            const from = startIdx + 1;
            const to = Math.min(startIdx + PAGE_SIZE, total);
            const tpl = m.showingPeriods || 'Showing :from–:to of :total';
            showing.textContent = tpl
                .replace(':from', from)
                .replace(':to', to)
                .replace(':total', total);
        } else {
            showing.textContent = m.periodsEmpty || '';
        }

        renderPagination(totalPages);
        bindPeriodRowActions(year);
    }

    function buildPeriodActions(period) {
        const m = msg();
        const isUpcoming = period.status === 'upcoming';
        const disableOpenClose = isUpcoming || period.status === 'closing';
        let html = '';

        if (period.status === 'closed') {
            html += actionBtn(
                'open',
                'fa-unlock',
                m.actionOpen,
                'btn-open',
                disableOpenClose
            );
        }
        if (period.status === 'open') {
            html += actionBtn(
                'close',
                'fa-lock',
                m.actionClose,
                'btn-close-period',
                disableOpenClose
            );
        }
        if (period.status === 'closing') {
            html += actionBtn(
                'close',
                'fa-pause',
                m.actionClose,
                'btn-close-period',
                true
            );
        }

        html += actionBtn('view', 'fa-eye', m.actionView, '', false);
        html += actionBtn('edit', 'fa-pen', m.actionEdit, '', false);

        return html;
    }

    function actionBtn(action, icon, title, extraClass, disabled) {
        const dis = disabled ? ' disabled' : '';
        return `<button type="button" class="fy-action-btn ${extraClass || ''}" data-action="${action}" title="${escapeAttr(title)}" data-bs-toggle="tooltip"${dis}>
            <i class="fas ${icon} fs-7"></i>
        </button>`;
    }

    function escapeAttr(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    function renderPagination(totalPages) {
        const ul = document.getElementById('fy-periods-pagination');
        if (!ul) {
            return;
        }
        let html = '';
        for (let i = 1; i <= totalPages; i++) {
            html += `<li class="page-item ${i === periodsPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        }
        ul.innerHTML = html;
        ul.querySelectorAll('[data-page]').forEach((link) => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                periodsPage = parseInt(this.dataset.page, 10);
                const year = findYear(api().getState(), selectedYearId);
                if (year) {
                    renderPeriodsTable(year);
                }
            });
        });
    }

    function bindPeriodRowActions(year) {
        const tbody = document.getElementById('fy-periods-tbody');
        tbody?.querySelectorAll('[data-action]').forEach((btn) => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (this.disabled) {
                    return;
                }
                const row = this.closest('tr');
                const periodId = row?.dataset.periodId;
                const period = findPeriod(year, periodId);
                if (!period) {
                    return;
                }
                const action = this.dataset.action;
                if (action === 'view') {
                    openViewModal(period);
                } else if (action === 'edit') {
                    openEditModal(year, period);
                } else if (action === 'close') {
                    confirmClosePeriod(year, period);
                } else if (action === 'open') {
                    confirmOpenPeriod(year, period);
                }
            });
        });
        initDetailTooltips();
    }

    function confirmClosePeriod(year, period) {
        const m = msg();
        if (period.status === 'upcoming') {
            toastr?.warning(m.periodActionDisabled);
            return;
        }
        const SwalApi = window.Swal;
        const runClose = () => {
            showPeriodsLoading(true);
            period.status = 'closing';
            renderPeriodsTable(year);
            setTimeout(() => {
                period.status = 'closed';
                api().saveState(api().getState());
                showPeriodsLoading(false);
                renderPeriodsTable(year);
                toastr?.success(m.periodClosedSuccess);
            }, 600);
        };

        if (SwalApi?.fire) {
            SwalApi.fire({
                title: m.confirmCloseTitle,
                text: (m.confirmCloseText || '').replace(':name', period.name),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: m.confirmYesClose,
                cancelButtonText: m.cancel,
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
        } else if (confirm(m.confirmCloseTitle)) {
            runClose();
        }
    }

    function confirmOpenPeriod(year, period) {
        const m = msg();
        if (period.status === 'upcoming') {
            toastr?.warning(m.periodActionDisabled);
            return;
        }
        const SwalApi = window.Swal;
        const runOpen = () => {
            period.status = 'open';
            api().saveState(api().getState());
            renderPeriodsTable(year);
            toastr?.success(m.periodOpenedSuccess);
        };

        if (SwalApi?.fire) {
            SwalApi.fire({
                title: m.confirmOpenTitle,
                text: (m.confirmOpenText || '').replace(':name', period.name),
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: m.confirmYesOpen,
                cancelButtonText: m.cancel,
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
        } else if (confirm(m.confirmOpenTitle)) {
            runOpen();
        }
    }

    function openViewModal(period) {
        const a = api();
        const content = document.getElementById('fy-period-view-content');
        const lbl = window.fyPeriodViewLabels || {};
        content.innerHTML = `
            <div class="fy-detail-row"><dt>${lbl.name || 'Name'}</dt><dd class="fw-bold">${a.escapeHtml(period.name)}</dd></div>
            <div class="fy-detail-row"><dt>${lbl.start || 'Start'}</dt><dd class="fy-date-num">${a.formatDisplayDate(period.start_date)}</dd></div>
            <div class="fy-detail-row"><dt>${lbl.end || 'End'}</dt><dd class="fy-date-num">${a.formatDisplayDate(period.end_date)}</dd></div>
            <div class="fy-detail-row"><dt>${lbl.status || 'Status'}</dt><dd>${periodStatusBadge(period.status)}</dd></div>
        `;
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('fyPeriodViewModal')
        ).show();
    }

    function openEditModal(year, period) {
        document.getElementById('fy-edit-period-id').value = period.id;
        document.getElementById('fy-edit-period-name').value = period.name;
        document.getElementById('fy-edit-period-start').value = period.start_date;
        document.getElementById('fy-edit-period-end').value = period.end_date;
        initEditPickers();
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById('fyPeriodEditModal')
        ).show();
    }

    function initEditPickers() {
        if (typeof flatpickr === 'undefined') {
            return;
        }
        const opts = { dateFormat: 'Y-m-d', allowInput: true };
        if (api()?.cfg?.locale === 'ar' && flatpickr.l10ns?.ar) {
            opts.locale = flatpickr.l10ns.ar;
        }
        if (!editFpStart) {
            editFpStart = flatpickr('#fy-edit-period-start', opts);
            editFpEnd = flatpickr('#fy-edit-period-end', opts);
        }
    }

    function showPeriodsLoading(show) {
        document
            .getElementById('fy-periods-loading')
            ?.classList.toggle('is-active', show);
    }

    function initDetailTooltips() {
        document
            .querySelectorAll('#fy-year-detail-view [data-bs-toggle="tooltip"]')
            .forEach((el) => {
                const existing = bootstrap.Tooltip.getInstance(el);
                if (existing) {
                    existing.dispose();
                }
                new bootstrap.Tooltip(el);
            });
    }

    function bindGlobalEvents() {
        document.getElementById('fy-detail-back')?.addEventListener('click', showListView);

        document.getElementById('fy-periods-search')?.addEventListener('input', function () {
            periodsSearch = this.value;
            periodsPage = 1;
            const year = findYear(api()?.getState(), selectedYearId);
            if (year) {
                renderPeriodsTable(year);
            }
        });

        document.querySelectorAll('.fy-periods-thead .fy-sortable').forEach((th) => {
            th.addEventListener('click', function () {
                const field = this.dataset.sort;
                if (periodsSort.field === field) {
                    periodsSort.dir = periodsSort.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    periodsSort.field = field;
                    periodsSort.dir = 'asc';
                }
                const year = findYear(api()?.getState(), selectedYearId);
                if (year) {
                    renderPeriodsTable(year);
                }
            });
        });

        document.getElementById('fy-period-edit-form')?.addEventListener('submit', function (e) {
            e.preventDefault();
            const a = api();
            const state = a.getState();
            const year = findYear(state, selectedYearId);
            const periodId = document.getElementById('fy-edit-period-id').value;
            const period = findPeriod(year, periodId);
            if (!period) {
                return;
            }
            period.name = document.getElementById('fy-edit-period-name').value.trim();
            period.start_date = document.getElementById('fy-edit-period-start').value.trim();
            period.end_date = document.getElementById('fy-edit-period-end').value.trim();
            a.saveState(state);
            bootstrap.Modal.getInstance(
                document.getElementById('fyPeriodEditModal')
            )?.hide();
            renderPeriodsTable(year);
            toastr?.success(msg().periodUpdatedSuccess);
        });
    }

    function tryOpenFromUrl() {
        const yearParam = new URL(window.location.href).searchParams.get('year');
        if (yearParam && findYear(api()?.getState(), yearParam)) {
            showDetailView(yearParam);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const wait = setInterval(function () {
            if (!window.fySettingsApi) {
                return;
            }
            clearInterval(wait);

            window.fySettingsApi.openYearDetail = showDetailView;
            window.fySettingsApi.closeYearDetail = showListView;

            bindGlobalEvents();
            tryOpenFromUrl();
        }, 50);
    });
})();
