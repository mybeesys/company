(function () {
    'use strict';

    const cfg = window.fyClosePageConfig || {};
    const msg = cfg.messages || {};

    let readiness = null;
    let preview = null;
    let currentStep = 1;

    function periodQuery() {
        return cfg.periodId ? `?period_id=${encodeURIComponent(cfg.periodId)}` : '';
    }

    async function apiRequest(method, url) {
        const res = await fetch(url, {
            method,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': cfg.csrfToken || '',
            },
            credentials: 'same-origin',
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data.message || msg.apiError || 'Request failed');
        }
        return data;
    }

    function formatMoney(value) {
        const n = Number(value || 0);
        return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function $(id) {
        return document.getElementById(id);
    }

    function showError(message) {
        $('fy-close-loading')?.classList.add('d-none');
        $('fy-close-main')?.classList.add('d-none');
        const el = $('fy-close-error');
        if (el) {
            el.textContent = message;
            el.classList.remove('d-none');
        }
    }

    function showMain() {
        $('fy-close-loading')?.classList.add('d-none');
        $('fy-close-error')?.classList.add('d-none');
        $('fy-close-main')?.classList.remove('d-none');
    }

    function renderReadiness() {
        const container = $('fy-close-readiness');
        if (!container || !readiness) {
            return;
        }

        const blockers = (readiness.blocking_messages || [])
            .map((text) => `<li class="text-danger">${text}</li>`)
            .join('');
        const warnings = (readiness.warnings || [])
            .map((text) => `<li class="text-warning">${text}</li>`)
            .join('');
        const routingOk = readiness.routing_complete;
        const routingLabel = routingOk ? msg.fiscalCloseRoutingReady : msg.fiscalCloseRoutingMissing;

        let modeBadge = '';
        if (readiness.is_repair) {
            modeBadge = `<span class="badge badge-light-warning">${msg.fiscalCloseRepairBadge || ''}</span>`;
        } else if (readiness.is_remedial) {
            modeBadge = `<span class="badge badge-light-info">${msg.fiscalCloseRemedialBadge || ''}</span>`;
        }

        container.innerHTML = `
            <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
                <span class="badge ${routingOk ? 'badge-light-success' : 'badge-light-danger'}">${routingLabel}</span>
                ${modeBadge}
            </div>
            ${blockers ? `<div class="mb-3"><div class="fw-bold mb-2">${msg.fiscalCloseWizardBlockers}</div><ul class="mb-0 ps-5">${blockers}</ul></div>` : ''}
            ${warnings ? `<div class="mb-0"><div class="fw-bold mb-2">${msg.fiscalCloseWizardWarnings}</div><ul class="mb-0 ps-5">${warnings}</ul></div>` : ''}
        `;
    }

    function renderPreview() {
        if (!preview) {
            return;
        }

        const totals = preview.totals || {};
        const balanced = totals.is_balanced;

        const badge = $('fy-close-balance-badge');
        if (badge) {
            badge.innerHTML = balanced
                ? `<span class="badge badge-light-success">${msg.fiscalCloseWizardBalanced}</span>`
                : `<span class="badge badge-light-danger">${msg.fiscalCloseWizardUnbalanced}</span>`;
        }

        const summary = $('fy-close-summary');
        if (summary) {
            summary.innerHTML = `
                <div class="fy-close-summary-item">
                    <div class="label">${msg.fiscalCloseWizardJournalDate}</div>
                    <div class="value">${preview.journal_date || '—'}</div>
                </div>
                <div class="fy-close-summary-item">
                    <div class="label">${msg.fiscalCloseWizardTotalIncome}</div>
                    <div class="value">${formatMoney(totals.total_income)}</div>
                </div>
                <div class="fy-close-summary-item">
                    <div class="label">${msg.fiscalCloseWizardTotalExpenses}</div>
                    <div class="value">${formatMoney(totals.total_expenses)}</div>
                </div>
                <div class="fy-close-summary-item">
                    <div class="label">${msg.fiscalCloseWizardNetIncome}</div>
                    <div class="value">${formatMoney(totals.net_income)}</div>
                </div>
                <div class="fy-close-summary-item">
                    <div class="label">${msg.fiscalCloseWizardPlAccounts}</div>
                    <div class="value">${totals.pl_accounts_count || 0}</div>
                </div>
            `;
        }

        const linesWrap = $('fy-close-lines');
        const lines = preview.lines || [];
        if (linesWrap) {
            if (!lines.length) {
                linesWrap.innerHTML = `<div class="alert alert-light mb-0">${msg.fiscalCloseWizardNoLines}</div>`;
            } else {
                const rows = lines
                    .map(
                        (line) => `
                        <tr>
                            <td>${line.account_label || ''}</td>
                            <td class="text-end">${line.debit > 0 ? formatMoney(line.debit) : '—'}</td>
                            <td class="text-end">${line.credit > 0 ? formatMoney(line.credit) : '—'}</td>
                            <td class="text-muted fs-7">${line.description || ''}</td>
                        </tr>`
                    )
                    .join('');

                linesWrap.innerHTML = `
                    <table class="table table-row-bordered table-row-gray-200 align-middle gy-4">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th>${msg.fiscalCloseWizardColAccount}</th>
                                <th class="text-end">${msg.fiscalCloseWizardColDebit}</th>
                                <th class="text-end">${msg.fiscalCloseWizardColCredit}</th>
                                <th>${msg.fiscalCloseWizardColDescription}</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                `;
            }
        }

        const note = $('fy-close-preview-note');
        if (note) {
            note.textContent = preview.note || '';
        }
    }

    function setStep(step) {
        currentStep = step;

        document.querySelectorAll('.fy-close-stepper .step').forEach((el) => {
            const n = Number(el.dataset.step);
            el.classList.remove('active', 'done');
            if (n < step) {
                el.classList.add('done');
            } else if (n === step) {
                el.classList.add('active');
            }
        });

        document.querySelectorAll('.fy-close-panel').forEach((el) => {
            el.classList.toggle('active', Number(el.dataset.panel) === step);
        });

        updateButtons();
    }

    function updateButtons() {
        const btnBack = $('fy-close-btn-back');
        const btnNext = $('fy-close-btn-next');
        const btnRouting = $('fy-close-btn-routing');
        const btnExecute = $('fy-close-btn-execute');

        btnBack?.classList.toggle('d-none', currentStep <= 1);
        btnNext?.classList.add('d-none');
        btnRouting?.classList.add('d-none');
        btnExecute?.classList.add('d-none');

        if (!readiness) {
            return;
        }

        if (currentStep === 1) {
            if (!readiness.routing_complete) {
                btnRouting?.classList.remove('d-none');
            } else if (readiness.can_preview) {
                btnNext?.classList.remove('d-none');
            }
            return;
        }

        if (currentStep === 2) {
            btnNext?.classList.remove('d-none');
            return;
        }

        if (currentStep === 3 && readiness.can_preview) {
            btnExecute?.classList.remove('d-none');
            if (readiness.is_repair && msg.fiscalCloseExecuteRepair) {
                btnExecute.textContent = msg.fiscalCloseExecuteRepair;
            }
        }
    }

    async function loadData() {
        const query = periodQuery();

        const readinessPayload = await apiRequest('GET', cfg.api.readiness + query);
        readiness = readinessPayload.readiness || {};

        if (!readiness.is_year_end_boundary) {
            showError(readiness.blocking_messages?.[0] || msg.apiError);
            return;
        }

        renderReadiness();

        if (readiness.can_preview) {
            try {
                const previewPayload = await apiRequest('GET', cfg.api.preview + query);
                preview = previewPayload.preview;
                renderPreview();
            } catch (error) {
                showError(error.message);
                return;
            }
        }

        showMain();
        setStep(1);
    }

    async function runExecute() {
        const btnExecute = $('fy-close-btn-execute');
        const originalHtml = btnExecute?.innerHTML;
        if (btnExecute) {
            btnExecute.disabled = true;
            btnExecute.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${msg.fiscalCloseWizardExecuting}`;
        }

        try {
            const executeResult = await apiRequest('POST', cfg.api.execute + periodQuery());

            if (executeResult.already_posted) {
                toastr?.info(executeResult.message);
            } else {
                toastr?.success(executeResult.message || msg.fiscalCloseExecuteSuccess);
            }

            const journalId = executeResult.journal_id;
            const resultBox = $('fy-close-execute-result');
            if (resultBox && journalId) {
                const journalUrl = `${cfg.journalShowBaseUrl}/${journalId}`;
                resultBox.innerHTML = `
                    <div class="alert alert-light-success border border-success border-dashed mb-4">
                        ${executeResult.message || msg.fiscalCloseExecuteSuccess}
                        <div class="mt-3">
                            <a href="${journalUrl}" class="btn btn-sm btn-light-primary" target="_blank" rel="noopener">${msg.fiscalCloseViewJournal}</a>
                        </div>
                    </div>
                `;
                resultBox.classList.remove('d-none');
            }

            // Remedial/repair: year may already be admin-closed — only close if still open.
            const yearAlreadyClosed = readiness?.year_open === false;
            if (!yearAlreadyClosed) {
                const closeUrl = cfg.closeTarget === 'period' ? cfg.api.closePeriod : cfg.api.closeYear;
                if (!closeUrl) {
                    throw new Error(msg.apiError);
                }
                await apiRequest('POST', closeUrl);
                toastr?.success(msg.pageAdminCloseSuccess);
            }

            window.location.href = cfg.backUrl;
        } catch (error) {
            toastr?.error(error.message);
            if (btnExecute) {
                btnExecute.disabled = false;
                btnExecute.innerHTML = originalHtml;
            }
        }
    }

    function bindEvents() {
        $('fy-close-btn-back')?.addEventListener('click', () => {
            if (currentStep > 1) {
                setStep(currentStep - 1);
            }
        });

        $('fy-close-btn-next')?.addEventListener('click', () => {
            if (currentStep === 1 && readiness?.can_preview) {
                setStep(2);
            } else if (currentStep === 2) {
                setStep(3);
            }
        });

        $('fy-close-btn-execute')?.addEventListener('click', () => {
            runExecute();
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        bindEvents();
        loadData().catch((error) => showError(error.message));
    });
})();
