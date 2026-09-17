const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

async function getJson(url) {
    const res = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf() || '',
        },
    });
    const body = await res.json().catch(() => ({}));
    if (!res.ok || body.ok === false) {
        throw new Error(body.error || `HTTP ${res.status}`);
    }
    return body.data;
}

function withFilters(url, filters, extra = {}) {
    const params = new URLSearchParams();
    Object.entries({ ...filters, ...extra }).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '' && value !== 'all' && key !== 'channel') {
            params.set(key, String(value));
        }
    });
    const qs = params.toString();
    return `${url}${qs ? `?${qs}` : ''}`;
}

export function fetchBootstrap(url, filters) {
    return getJson(withFilters(url, filters));
}

export function getExecutiveDashboardData(url, filters) {
    return getJson(withFilters(url, filters));
}

export function widgetUrl(base, widget, filters, extra = {}) {
    return withFilters(`${base}/${widget}`, filters, extra);
}

export function fetchWidget(base, widget, filters, extra) {
    return getJson(widgetUrl(base, widget, filters, extra));
}
