(function () {
    'use strict';

    const root = document.getElementById('dashboardCommandCenter');
    if (!root) {
        return;
    }

    const sections = Array.from(root.querySelectorAll('.cc-section[data-section-id]'));
    const navLinks = Array.from(document.querySelectorAll('#dashboardSectionNav [data-section-target]'));
    const MAX_IFRAME_HEIGHT = 16000;
    const loaded = new WeakSet();
    const resizeTimers = new WeakMap();

    function setUrlSection(sectionId) {
        if (!window.history.replaceState) {
            return;
        }
        const url = new URL(window.location.href);
        if (!sectionId || sectionId === 'overview') {
            url.searchParams.delete('tab');
        } else {
            url.searchParams.set('tab', sectionId);
        }
        url.hash = 'section-' + sectionId;
        history.replaceState(null, '', url.toString());
    }

    function setActiveNav(sectionId) {
        navLinks.forEach(function (link) {
            const isActive = link.getAttribute('data-section-target') === sectionId;
            link.classList.toggle('is-active', isActive);
        });
        root.setAttribute('data-active-section', sectionId || 'overview');
    }

    function clearResizeRetries(iframe) {
        const timers = resizeTimers.get(iframe) || [];
        timers.forEach(clearTimeout);
        resizeTimers.set(iframe, []);
    }

    function requestEmbedResize(iframe) {
        try {
            iframe?.contentWindow?.reportEmbedHeight?.();
        } catch (e) {
            /* ignore cross-origin / unloaded */
        }
    }

    function scheduleResizeRetries(iframe) {
        clearResizeRetries(iframe);
        const timers = [150, 600, 1500, 3000].map(function (ms) {
            return setTimeout(function () {
                requestEmbedResize(iframe);
            }, ms);
        });
        resizeTimers.set(iframe, timers);
    }

    function setIframeHeight(iframe, height) {
        if (!iframe || !height) {
            return;
        }
        const h = Math.min(Math.max(Math.ceil(height), 200), MAX_IFRAME_HEIGHT);
        const current = parseInt(iframe.style.height || '0', 10) || 0;
        if (Math.abs(h - current) < 8) {
            return;
        }
        iframe.style.height = h + 'px';
    }

    function hideLoading(section) {
        const loading = section.querySelector('[data-embed-loading]');
        loading?.classList.add('is-hidden');
    }

    function showLoading(section) {
        const loading = section.querySelector('[data-embed-loading]');
        loading?.classList.remove('is-hidden');
    }

    function loadEmbed(section) {
        const url = section.getAttribute('data-embed-url');
        const iframe = section.querySelector('[data-dashboard-embed]');
        if (!url || !iframe || loaded.has(iframe)) {
            return;
        }

        loaded.add(iframe);
        showLoading(section);

        iframe.onload = function () {
            hideLoading(section);
            iframe.classList.add('is-visible');
            requestEmbedResize(iframe);
            scheduleResizeRetries(iframe);
        };

        iframe.src = url;
    }

    function scrollToSection(sectionId, behavior) {
        const el = root.querySelector('.cc-section[data-section-id="' + sectionId + '"]');
        if (!el) {
            return;
        }
        el.scrollIntoView({ behavior: behavior || 'smooth', block: 'start' });
        setActiveNav(sectionId);
        setUrlSection(sectionId);
        if (el.getAttribute('data-embed-url')) {
            loadEmbed(el);
        }
    }

    navLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const id = link.getAttribute('data-section-target');
            if (id) {
                scrollToSection(id, 'smooth');
            }
        });
    });

    window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin) {
            return;
        }
        if (!event.data || event.data.type !== 'dashboard-embed-height' || !event.data.height) {
            return;
        }

        sections.forEach(function (section) {
            const iframe = section.querySelector('[data-dashboard-embed]');
            if (iframe && event.source === iframe.contentWindow) {
                setIframeHeight(iframe, event.data.height);
                hideLoading(section);
                iframe.classList.add('is-visible');
            }
        });
    });

    if ('IntersectionObserver' in window) {
        const loadObserver = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        loadEmbed(entry.target);
                    }
                });
            },
            { root: null, rootMargin: '240px 0px', threshold: 0.01 }
        );

        const activeObserver = new IntersectionObserver(
            function (entries) {
                const visible = entries
                    .filter(function (e) {
                        return e.isIntersecting;
                    })
                    .sort(function (a, b) {
                        return b.intersectionRatio - a.intersectionRatio;
                    });
                if (visible[0]) {
                    const id = visible[0].target.getAttribute('data-section-id');
                    if (id) {
                        setActiveNav(id);
                    }
                }
            },
            { root: null, rootMargin: '-20% 0px -55% 0px', threshold: [0.1, 0.25, 0.5, 0.75] }
        );

        sections.forEach(function (section) {
            if (section.getAttribute('data-embed-url')) {
                loadObserver.observe(section);
            }
            activeObserver.observe(section);
        });
    } else {
        sections.forEach(function (section) {
            if (section.getAttribute('data-embed-url')) {
                loadEmbed(section);
            }
        });
    }

    // Initial deep-link: ?tab= or #section-
    const initial =
        root.getAttribute('data-active-section') ||
        (window.location.hash || '').replace(/^#section-/, '') ||
        'overview';

    if (initial && initial !== 'overview') {
        setTimeout(function () {
            scrollToSection(initial, 'auto');
        }, 60);
    } else {
        setActiveNav('overview');
    }
})();
