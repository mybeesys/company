(function () {
    'use strict';

    const cfg = window.franchiseHubConfig || { activeTab: 'companies', tabs: {} };
    const tabButtons = document.querySelectorAll('#franchiseHubTabs [data-franchise-tab]');
    const companiesWrap = document.getElementById('franchise-hub-companies-wrap');
    const embedWrap = document.getElementById('franchise-hub-embed-wrap');
    const iframe = document.getElementById('franchise-hub-iframe');
    const loadingEl = document.getElementById('franchise-hub-loading');
    const loadedUrls = {};
    let resizeRetries = [];
    let lastIframeHeight = 0;
    const MAX_IFRAME_HEIGHT = 16000;

    function clearResizeRetries() {
        resizeRetries.forEach(clearTimeout);
        resizeRetries = [];
    }

    function requestEmbedResize() {
        try {
            iframe?.contentWindow?.reportEmbedHeight?.();
        } catch (e) {
            /* ignore */
        }
    }

    function scheduleResizeRetries() {
        clearResizeRetries();
        [150, 600, 1500, 3000].forEach(function (ms) {
            resizeRetries.push(setTimeout(requestEmbedResize, ms));
        });
    }

    function setIframeHeight(height) {
        if (!iframe || !height) {
            return;
        }

        const h = Math.min(Math.max(Math.ceil(height), 200), MAX_IFRAME_HEIGHT);
        if (Math.abs(h - lastIframeHeight) < 8) {
            return;
        }

        lastIframeHeight = h;
        iframe.style.height = h + 'px';
    }

    function onEmbedLoaded(url) {
        loadingEl?.classList.add('d-none');
        iframe.classList.add('is-visible');
        loadedUrls[url] = true;
        lastIframeHeight = 0;
        requestEmbedResize();
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

    function setUrlTab(tabId) {
        if (!window.history.replaceState) {
            return;
        }
        const url = new URL(window.location.href);
        if (!tabId || tabId === 'companies') {
            url.searchParams.delete('tab');
        } else {
            url.searchParams.set('tab', tabId);
        }
        history.replaceState(null, '', url.toString());
    }

    function showCompanies() {
        clearResizeRetries();
        companiesWrap?.classList.remove('d-none');
        embedWrap?.classList.add('d-none');
        if (iframe) {
            iframe.classList.remove('is-visible');
            iframe.removeAttribute('src');
            iframe.style.height = '0';
            lastIframeHeight = 0;
        }
        loadingEl?.classList.add('d-none');
        if (typeof window.initFranchiseCompaniesTable === 'function') {
            window.initFranchiseCompaniesTable();
        }
        if (window.companiesTable) {
            window.companiesTable.columns.adjust();
        }
    }

    function showEmbed(url) {
        companiesWrap?.classList.add('d-none');
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
            requestEmbedResize();
            scheduleResizeRetries();
            return;
        }

        loadingEl?.classList.remove('d-none');
        iframe.classList.remove('is-visible');
        lastIframeHeight = 0;
        iframe.style.height = '200px';
        iframe.src = url;
    }

    function activateTab(tabId, pushUrl) {
        const meta = cfg.tabs[tabId];
        if (!meta) {
            return;
        }

        tabButtons.forEach(function (btn) {
            const isActive = btn.getAttribute('data-franchise-tab') === tabId;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        if (meta.type === 'inline') {
            showCompanies();
        } else if (meta.embed_url) {
            showEmbed(meta.embed_url);
        }

        if (pushUrl !== false) {
            setUrlTab(tabId);
        }
    }

    tabButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            activateTab(btn.getAttribute('data-franchise-tab'));
        });
    });

    activateTab(cfg.activeTab || 'companies', false);
})();
