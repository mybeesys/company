(function () {
    'use strict';

    function accountingClosePageUrl(year, period) {
        const cfg = window.fySettingsConfig;
        const base = cfg?.api?.accountingClosePage
            ? cfg.api.accountingClosePage(year.id)
            : `/accounting/financial-years/${year.id}/accounting-close`;
        const q = period?.id ? `?period_id=${encodeURIComponent(period.id)}` : '';
        return base + q;
    }

    function isLastPeriodInYear(year, period) {
        if (!year?.periods?.length) {
            return true;
        }
        const sorted = [...year.periods].sort((a, b) => {
            if (a.end_date === b.end_date) {
                return 0;
            }
            return a.end_date > b.end_date ? 1 : -1;
        });
        return String(sorted[sorted.length - 1].id) === String(period.id);
    }

    function shouldShowAccountingWizard({ year, period }) {
        if (!year) {
            return false;
        }
        if (!period) {
            return true;
        }
        return isLastPeriodInYear(year, period);
    }

    async function runWizard({ year, period, label, onConfirmAdministrativeClose }) {
        const m = window.fySettingsConfig?.messages || {};
        const SwalApi = window.Swal;

        if (!shouldShowAccountingWizard({ year, period })) {
            const note = m.fiscalClosePeriodAdminNote || m.confirmCloseText;
            if (SwalApi?.fire) {
                const result = await SwalApi.fire({
                    title: m.confirmCloseTitle || m.confirmCloseYearTitle,
                    text: (m.confirmCloseText || '').replace(':name', label) + '\n\n' + note,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: m.confirmYesClose,
                    cancelButtonText: m.cancel,
                });
                if (result.isConfirmed) {
                    onConfirmAdministrativeClose();
                }
            } else if (confirm(label)) {
                onConfirmAdministrativeClose();
            }
            return;
        }

        window.location.href = accountingClosePageUrl(year, period);
    }

    window.FyAccountingCloseWizard = {
        runWizard,
        shouldShowAccountingWizard,
        isLastPeriodInYear,
        accountingClosePageUrl,
    };
})();
