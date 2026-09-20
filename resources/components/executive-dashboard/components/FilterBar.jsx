import React, { useMemo, useState } from 'react';
import { useDashboardFilters } from '../context/DashboardFilterContext';
import FilterMultiSelect from './FilterMultiSelect';

function FilterIcon() {
    return (
        <svg className="ed-hub-ico" viewBox="0 0 20 20" aria-hidden="true">
            <path
                d="M3.2 4.4h13.6L11.8 10v5.2L8.2 17V10L3.2 4.4Z"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.7"
                strokeLinejoin="round"
            />
        </svg>
    );
}

function formatDate(value, locale) {
    if (!value) return '';
    const [y, m, d] = String(value).split('-');
    if (!y || !m || !d) return value;
    return locale === 'ar' ? `${d}/${m}/${y}` : `${m}/${d}/${y}`;
}

export default function DashboardFilterBar({ bootstrap, locale, extraActions }) {
    const { draft, applied, patchDraft, apply, reset, applyPatch } = useDashboardFilters();
    const ar = locale === 'ar';
    const [open, setOpen] = useState(false);
    const branches = bootstrap?.options?.branches || [];
    const activities = bootstrap?.options?.activities || [];

    const branchOptions = useMemo(
        () => branches.map((b) => ({ value: String(b.id), label: b.name })),
        [branches]
    );
    const activityOptions = useMemo(
        () => activities.map((a) => ({ value: String(a.id), label: a.name })),
        [activities]
    );

    const branchNames = Object.fromEntries(branchOptions.map((o) => [o.value, o.label]));
    const activityNames = Object.fromEntries(activityOptions.map((o) => [o.value, o.label]));

    const chips = [];
    if (applied.start_date || applied.end_date) {
        chips.push({
            key: 'period',
            label: `${formatDate(applied.start_date, locale)} – ${formatDate(applied.end_date, locale)}`,
        });
    }
    (applied.branch_ids || []).forEach((id) => {
        chips.push({
            key: `b-${id}`,
            label: branchNames[String(id)] || (ar ? `فرع ${id}` : `Branch ${id}`),
            onRemove: () => applyPatch({
                branch_ids: (applied.branch_ids || []).filter((item) => String(item) !== String(id)),
            }),
        });
    });
    (applied.activity_ids || []).forEach((id) => {
        chips.push({
            key: `a-${id}`,
            label: activityNames[String(id)] || (ar ? `مركز ${id}` : `Center ${id}`),
            onRemove: () => applyPatch({
                activity_ids: (applied.activity_ids || []).filter((item) => String(item) !== String(id)),
            }),
        });
    });

    const submit = (e) => {
        e.preventDefault();
        apply();
        setOpen(false);
    };

    return (
        <>
            <header className="ed-hero">
                <div className="ed-hero-start">
                    <h1>{ar ? 'لوحة التحكم' : 'Dashboard'}</h1>
                    {chips.length > 0 && (
                        <div className="ed-applied" aria-label={ar ? 'الفلاتر المطبقة' : 'Applied filters'}>
                            {chips.map((chip) => (
                                <span key={chip.key} className="ed-applied-chip">
                                    <span>{chip.label}</span>
                                    {chip.onRemove ? (
                                        <button
                                            type="button"
                                            className="ed-applied-x"
                                            aria-label={ar ? 'إزالة' : 'Remove'}
                                            onClick={chip.onRemove}
                                        >
                                            ×
                                        </button>
                                    ) : null}
                                </span>
                            ))}
                        </div>
                    )}
                </div>
                <div className="ed-hero-actions">
                    <button
                        type="button"
                        className={`ed-hub-chip${open ? ' is-open' : ''}`}
                        aria-expanded={open ? 'true' : 'false'}
                        aria-controls="edFilterPanel"
                        onClick={() => setOpen((v) => !v)}
                    >
                        <FilterIcon />
                        <span>{ar ? 'الفلترة' : 'Filters'}</span>
                    </button>
                    {extraActions}
                </div>
            </header>
            {open ? (
                <form id="edFilterPanel" className="ed-filters" dir={ar ? 'rtl' : 'ltr'} onSubmit={submit}>
                    <div className="ed-field">
                        <label htmlFor="ed-start">{ar ? 'الفترة' : 'Period'}</label>
                        <div className="ed-date-pair">
                            <input id="ed-start" type="date" value={draft.start_date} onChange={(e) => patchDraft({ start_date: e.target.value })} />
                            <input id="ed-end" type="date" value={draft.end_date} onChange={(e) => patchDraft({ end_date: e.target.value })} />
                        </div>
                    </div>
                    <div className="ed-field ed-field-select">
                        <label htmlFor="ed-branch">{ar ? 'الفرع' : 'Branch'}</label>
                        <FilterMultiSelect
                            inputId="ed-branch"
                            options={branchOptions}
                            value={draft.branch_ids || []}
                            onChange={(branch_ids) => patchDraft({ branch_ids })}
                            placeholder={ar ? 'كل الفروع' : 'All branches'}
                            isRtl={ar}
                            noOptions={ar ? 'لا توجد فروع' : 'No branches'}
                        />
                    </div>
                    <div className="ed-field ed-field-select">
                        <label htmlFor="ed-activity">{ar ? 'مركز التكلفة' : 'Cost center'}</label>
                        <FilterMultiSelect
                            inputId="ed-activity"
                            options={activityOptions}
                            value={draft.activity_ids || []}
                            onChange={(activity_ids) => patchDraft({ activity_ids })}
                            placeholder={ar ? 'كل مراكز التكلفة' : 'All cost centers'}
                            isRtl={ar}
                            noOptions={ar ? 'لا توجد مراكز تكلفة' : 'No cost centers'}
                        />
                    </div>
                    <div className="ed-actions">
                        <button type="submit" className="ed-btn ed-btn-primary">{ar ? 'تطبيق' : 'Apply'}</button>
                        <button type="button" className="ed-btn ed-btn-ghost" onClick={reset}>{ar ? 'إلغاء الفلترة' : 'Reset'}</button>
                    </div>
                </form>
            ) : null}
        </>
    );
}
