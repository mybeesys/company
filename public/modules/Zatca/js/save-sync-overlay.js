(function (window, document) {
    'use strict';

    var stageTimer = null;
    var open = false;

    function i18n() {
        return window.zatcaSaveOverlayI18n || {};
    }

    function el() {
        return document.getElementById('zatca-save-overlay');
    }

    function readFlags(root, options) {
        options = options || {};
        return {
            docType: options.docType || (root && root.getAttribute('data-doc-type')) || 'invoice',
            instant: typeof options.instant === 'boolean'
                ? options.instant
                : !!(root && root.getAttribute('data-instant') === '1'),
            configured: typeof options.configured === 'boolean'
                ? options.configured
                : !!(root && root.getAttribute('data-configured') === '1'),
            status: String(options.status || 'final'),
        };
    }

    function setText(id, text) {
        var node = document.getElementById(id);
        if (node) {
            node.textContent = text || '';
        }
    }

    function renderSteps(flags, active) {
        var list = document.getElementById('zatca-save-overlay-steps');
        if (!list) {
            return;
        }

        var t = i18n();
        var showSync = flags.instant && flags.status !== 'draft';

        if (!showSync) {
            list.hidden = true;
            list.innerHTML = '';
            return;
        }

        list.hidden = false;
        list.innerHTML =
            '<li class="zatca-save-overlay__step" data-step="save">' +
            '<span class="zatca-save-overlay__bullet"></span><span>' + (t.step_save || '') + '</span></li>' +
            '<li class="zatca-save-overlay__step is-sync-step" data-step="sync">' +
            '<span class="zatca-save-overlay__bullet"></span><span>' + (t.step_sync || '') + '</span></li>' +
            '<li class="zatca-save-overlay__step" data-step="done">' +
            '<span class="zatca-save-overlay__bullet"></span><span>' + (t.step_done || '') + '</span></li>';

        Array.prototype.forEach.call(list.querySelectorAll('.zatca-save-overlay__step'), function (step) {
            var key = step.getAttribute('data-step');
            step.classList.remove('is-active', 'is-done');
            if (active === 'saving') {
                if (key === 'save') step.classList.add('is-active');
            } else if (active === 'syncing') {
                if (key === 'save') step.classList.add('is-done');
                if (key === 'sync') step.classList.add('is-active');
            } else if (active === 'redirecting') {
                if (key === 'save' || key === 'sync') step.classList.add('is-done');
                if (key === 'done') step.classList.add('is-active');
            }
        });
    }

    function applyStage(flags, stage) {
        var root = el();
        if (!root) {
            return;
        }
        var t = i18n();
        root.classList.toggle('is-sync', stage === 'syncing');

        if (flags.status === 'draft') {
            setText('zatca-save-overlay-title', t.saving_draft_title);
            setText('zatca-save-overlay-sub', t.saving_draft_sub);
            renderSteps(flags, 'saving');
            return;
        }

        if (stage === 'syncing' && flags.instant) {
            setText('zatca-save-overlay-title', t.sync_title);
            setText(
                'zatca-save-overlay-sub',
                flags.configured ? t.sync_sub : t.sync_sub_pending
            );
            renderSteps(flags, 'syncing');
            return;
        }

        if (stage === 'redirecting') {
            setText('zatca-save-overlay-title', t.redirect_title);
            setText('zatca-save-overlay-sub', t.redirect_sub);
            renderSteps(flags, 'redirecting');
            return;
        }

        setText('zatca-save-overlay-title', t.saving_title);
        setText(
            'zatca-save-overlay-sub',
            flags.docType === 'return' ? t.saving_sub_return : t.saving_sub_invoice
        );
        renderSteps(flags, 'saving');
    }

    function clearTimer() {
        if (stageTimer) {
            clearTimeout(stageTimer);
            stageTimer = null;
        }
    }

    function show(options) {
        var root = el();
        if (!root) {
            return;
        }

        var flags = readFlags(root, options);
        root._zatcaFlags = flags;
        clearTimer();
        applyStage(flags, 'saving');

        root.hidden = false;
        root.setAttribute('aria-hidden', 'false');
        root.setAttribute('aria-busy', 'true');
        // Force reflow before open class for animation.
        void root.offsetWidth;
        root.classList.add('is-open');
        open = true;
        document.documentElement.style.overflow = 'hidden';

        if (flags.status !== 'draft' && flags.instant) {
            stageTimer = setTimeout(function () {
                if (open) {
                    applyStage(flags, 'syncing');
                }
            }, 1100);
        }
    }

    function setStage(stage) {
        var root = el();
        if (!root || !open) {
            return;
        }
        applyStage(root._zatcaFlags || readFlags(root), stage);
    }

    function hide() {
        var root = el();
        clearTimer();
        open = false;
        document.documentElement.style.overflow = '';
        if (!root) {
            return;
        }
        root.classList.remove('is-open', 'is-sync');
        root.setAttribute('aria-hidden', 'true');
        root.removeAttribute('aria-busy');
        setTimeout(function () {
            if (!open) {
                root.hidden = true;
            }
        }, 220);
    }

    function bindClassicForm(formSelector) {
        var form = document.querySelector(formSelector || '#sell_save');
        if (!form || form.getAttribute('data-zatca-overlay-bound') === '1') {
            return;
        }
        form.setAttribute('data-zatca-overlay-bound', '1');

        form.addEventListener('submit', function () {
            var statusInput = form.querySelector('input[name="status"]');
            var status = statusInput && statusInput.value ? statusInput.value : 'final';
            show({ status: status });
        });
    }

    window.ZatcaSaveOverlay = {
        show: show,
        hide: hide,
        setStage: setStage,
        bindClassicForm: bindClassicForm,
    };

    document.addEventListener('DOMContentLoaded', function () {
        var root = el();
        if (root && root.getAttribute('data-bind-classic') === '1') {
            bindClassicForm('#sell_save');
        }
    });
})(window, document);
