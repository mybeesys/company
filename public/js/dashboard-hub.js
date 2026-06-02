(function () {
    'use strict';

    const cfg = window.dashboardHubConfig || { activeTab: 'overview', tabs: {} };
    const tabButtons = document.querySelectorAll('#dashboardHubTabs [data-dashboard-tab]');
    const overviewWrap = document.getElementById('dashboard-hub-overview-wrap');
    const embedWrap = document.getElementById('dashboard-hub-embed-wrap');
    const iframe = document.getElementById('dashboard-hub-iframe');
    const loadingEl = document.getElementById('dashboard-hub-loading');
    const loadedUrls = {};
    let resizeRetries = [];

    function clearResizeRetries() {
        resizeRetries.forEach(clearTimeout);
        resizeRetries = [];
    }

    function scheduleResizeRetries() {
        clearResizeRetries();
        [100, 400, 1200, 2500].forEach(function (ms) {
            resizeRetries.push(setTimeout(resizeIframeFromDocument, ms));
        });
    }

    function setIframeHeight(height) {
        if (!iframe || !height) {
            return;
        }
        iframe.style.height = Math.max(height, 320) + 'px';
    }

    function resizeIframeFromDocument() {
        if (!iframe) {
            return;
        }

        try {
            const doc = iframe.contentDocument || iframe.contentWindow?.document;
            if (!doc) {
                return;
            }

            const height = Math.max(
                doc.body?.scrollHeight || 0,
                doc.documentElement?.scrollHeight || 0,
                doc.body?.offsetHeight || 0,
                doc.documentElement?.offsetHeight || 0
            );

            if (height > 0) {
                setIframeHeight(height);
            }
        } catch (e) {
            /* cross-origin — should not happen for same-origin embeds */
        }
    }

    function onEmbedLoaded(url) {
        loadingEl?.classList.add('d-none');
        iframe.classList.add('is-visible');
        loadedUrls[url] = true;
        resizeIframeFromDocument();
        scheduleResizeRetries();
    }

    window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin) {
            return;
        }
        if (event.source !== iframe?.contentWindow) {
            return;
        }
        if (event.data?.type === 'dashboard-embed-height' && event.data.height) {
            setIframeHeight(event.data.height);
        }
    });

    window.addEventListener('resize', function () {
        if (iframe?.classList.contains('is-visible')) {
            resizeIframeFromDocument();
        }
    });

    function setUrlTab(tabId) {
        if (!window.history.replaceState) {
            return;
        }
        const url = new URL(window.location.href);
        if (!tabId || tabId === 'overview') {
            url.searchParams.delete('tab');
        } else {
            url.searchParams.set('tab', tabId);
        }
        history.replaceState(null, '', url.toString());
    }

    function showOverview() {
        clearResizeRetries();
        overviewWrap?.classList.remove('d-none');
        embedWrap?.classList.add('d-none');
        if (iframe) {
            iframe.classList.remove('is-visible');
            iframe.removeAttribute('src');
            iframe.style.height = '';
        }
        loadingEl?.classList.add('d-none');
    }

    function showEmbed(url) {
        overviewWrap?.classList.add('d-none');
        embedWrap?.classList.remove('d-none');

        if (!iframe || !url) {
            return;
        }

        iframe.onload = function () {
            onEmbedLoaded(url);
        };

        if (loadedUrls[url] && iframe.src) {
            loadingEl?.classList.add('d-none');
            iframe.classList.add('is-visible');
            resizeIframeFromDocument();
            scheduleResizeRetries();
            return;
        }

        loadingEl?.classList.remove('d-none');
        iframe.classList.remove('is-visible');
        iframe.style.height = '320px';
        iframe.src = url;
    }

    function activateTab(tabId, pushUrl) {
        const meta = cfg.tabs[tabId];
        if (!meta) {
            return;
        }

        tabButtons.forEach(function (btn) {
            const isActive = btn.getAttribute('data-dashboard-tab') === tabId;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        if (meta.type === 'inline') {
            showOverview();
        } else if (meta.embed_url) {
            showEmbed(meta.embed_url);
        }

        if (pushUrl !== false) {
            setUrlTab(tabId);
        }
    }

    tabButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activateTab(btn.getAttribute('data-dashboard-tab'));
        });
    });

    activateTab(cfg.activeTab || 'overview', false);
})();
