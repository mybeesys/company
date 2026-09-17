import React from 'react';
import { useDashboardFilters } from '../context/DashboardFilterContext';

export default function DashboardFilterBar({ bootstrap, locale }) {
    const { draft, patchDraft, apply, reset } = useDashboardFilters();
    const ar = locale === 'ar';
    const branches = bootstrap?.options?.branches || [];
    const activities = bootstrap?.options?.activities || [];

    return (
        <form
            className="ed-filters"
            dir={ar ? 'rtl' : 'ltr'}
            onSubmit={(e) => {
                e.preventDefault();
                apply();
            }}
        >
            <div className="ed-field">
                <label htmlFor="ed-start">{ar ? 'الفترة' : 'Period'}</label>
                <div className="ed-date-pair">
                    <input id="ed-start" type="date" value={draft.start_date} onChange={(e) => patchDraft({ start_date: e.target.value })} />
                    <input id="ed-end" type="date" value={draft.end_date} onChange={(e) => patchDraft({ end_date: e.target.value })} />
                </div>
            </div>
            <div className="ed-field">
                <label htmlFor="ed-branch">{ar ? 'الفرع' : 'Branch'}</label>
                <select id="ed-branch" value={draft.branch_id} onChange={(e) => patchDraft({ branch_id: e.target.value })}>
                    <option value="">{ar ? 'كل الفروع' : 'All branches'}</option>
                    {branches.map((b) => (
                        <option key={b.id} value={b.id}>{b.name}</option>
                    ))}
                </select>
            </div>
            <div className="ed-field">
                <label htmlFor="ed-activity">{ar ? 'النشاط' : 'Activity'}</label>
                <select id="ed-activity" value={draft.activity_id} onChange={(e) => patchDraft({ activity_id: e.target.value })}>
                    <option value="">{ar ? 'كل الأنشطة' : 'All activities'}</option>
                    {activities.map((a) => (
                        <option key={a.id} value={a.id}>{a.name}</option>
                    ))}
                </select>
            </div>
            <div className="ed-actions">
                <button type="submit" className="ed-btn ed-btn-primary">{ar ? 'تطبيق' : 'Apply'}</button>
                <button type="button" className="ed-btn ed-btn-ghost" onClick={reset}>{ar ? 'إلغاء الفلترة' : 'Reset'}</button>
            </div>
        </form>
    );
}
