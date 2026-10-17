(function () {
    const root = document.getElementById('cashier_order_settings_root');
    if (!root) {
        return;
    }

    const form = document.getElementById('cashier_order_settings_form');
    const presetInput = document.getElementById('cashier_order_preset');

    const presets = {
        retail: {
            reset_policy: 'daily',
            allocation: 'central',
            cycle_enabled: false,
            cycle_max: 99,
            padding: 3,
            start_number: 1,
            show_terminal_in_display: false,
            kitchen_same_as_display: true,
            format_pattern: '{padded}',
            offline_policy: 'refuse',
            prefix_style: 'letter',
            display_size: 'large',
        },
        qsr: {
            reset_policy: 'shift',
            allocation: 'prefix',
            cycle_enabled: true,
            cycle_max: 99,
            padding: 2,
            start_number: 1,
            show_terminal_in_display: true,
            kitchen_same_as_display: true,
            format_pattern: '{prefix}{separator}{padded}',
            offline_policy: 'terminal_prefix',
            prefix_style: 'letter',
            display_size: 'xlarge',
        },
        multi_pos: {
            reset_policy: 'daily',
            allocation: 'prefix',
            cycle_enabled: false,
            cycle_max: 99,
            padding: 3,
            start_number: 1,
            show_terminal_in_display: true,
            kitchen_same_as_display: true,
            format_pattern: '{prefix}{separator}{padded}',
            offline_policy: 'terminal_prefix',
            prefix_style: 'letter',
            display_size: 'xlarge',
        },
    };

    function checked(name) {
        const el = form.querySelector('[name="' + name + '"]:checked') || form.querySelector('[name="' + name + '"][type="checkbox"]:not([type="hidden"])');
        if (!el) {
            return '';
        }
        if (el.type === 'checkbox') {
            return el.checked;
        }
        return el.value;
    }

    function inputValue(name) {
        const el = form.querySelector('[name="' + name + '"]');
        return el ? el.value : '';
    }

    function setRadio(name, value) {
        form.querySelectorAll('[name="' + name + '"]').forEach(function (el) {
            el.checked = el.value === value;
        });
    }

    function setCheckbox(name, on) {
        form.querySelectorAll('[name="' + name + '"][type="checkbox"]').forEach(function (el) {
            el.checked = !!on;
        });
    }

    function setValue(name, value) {
        const el = form.querySelector('[name="' + name + '"]');
        if (el && el.type !== 'checkbox' && el.type !== 'radio' && el.type !== 'hidden') {
            el.value = value;
        }
    }

    function syncOptionCards() {
        root.querySelectorAll('.cashier-order-option').forEach(function (card) {
            const radio = card.querySelector('input[type="radio"]');
            if (!radio) {
                return;
            }
            card.classList.toggle('is-active', radio.checked);
        });
    }

    function toggleDepends() {
        const reset = checked('reset_policy');
        root.querySelectorAll('[data-depends="reset_policy"]').forEach(function (el) {
            el.classList.toggle('d-none', el.getAttribute('data-show-when') !== reset);
        });

        const cycleOn = !!form.querySelector('[data-toggle-cycle]:checked');
        root.querySelectorAll('[data-cycle-fields]').forEach(function (el) {
            el.classList.toggle('d-none', !cycleOn);
        });

        const diningOn = !!form.querySelector('[data-toggle-dining]:checked');
        root.querySelectorAll('[data-dining-fields]').forEach(function (el) {
            el.classList.toggle('d-none', !diningOn);
        });

        const allocation = checked('allocation');
        root.querySelectorAll('[data-range-cols]').forEach(function (el) {
            el.classList.toggle('d-none', allocation !== 'range');
        });
    }

    function firstTerminalPrefix() {
        const input = form.querySelector('[name^="terminals["][name$="[prefix]"]');
        return input && input.value ? input.value : 'A';
    }

    function firstDiningPrefix() {
        if (!form.querySelector('[data-toggle-dining]:checked')) {
            return '';
        }
        const input = form.querySelector('[name^="dining_type_prefixes["]');
        return input && input.value ? input.value : 'D';
    }

    function pad(seq, width) {
        const str = String(seq);
        const size = Math.max(1, parseInt(width, 10) || 3);
        return str.length >= size ? str : new Array(size - str.length + 1).join('0') + str;
    }

    function collapseSeparators(value, separator) {
        let out = String(value || '').trim();
        if (!separator) {
            return out;
        }
        const escaped = separator.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        out = out.replace(new RegExp('(?:\\s*' + escaped + '\\s*){2,}', 'g'), separator);
        const trimRe = new RegExp('^[\\s' + escaped + ']+|[\\s' + escaped + ']+$', 'g');
        return out.replace(trimRe, '');
    }

    function previewSeq() {
        let seq = 45;
        const start = parseInt(inputValue('start_number'), 10) || 1;
        const cycleOn = !!form.querySelector('[data-toggle-cycle]:checked');
        const max = parseInt(inputValue('cycle_max'), 10) || 99;
        if (cycleOn && max > 0) {
            const span = max - start + 1;
            if (span > 0) {
                seq = start + ((seq - start) % span);
                if (seq < start) {
                    seq = start;
                }
            }
        }
        return seq;
    }

    function updatePreview() {
        const seq = previewSeq();
        const padded = pad(seq, inputValue('padding'));
        const showTerminal = !!form.querySelector('[name="show_terminal_in_display"][type="checkbox"]:checked');
        const allocation = checked('allocation');
        let prefix = inputValue('global_prefix');
        if (showTerminal || allocation !== 'central') {
            prefix = firstTerminalPrefix() || prefix;
        }
        const dining = firstDiningPrefix();
        const separator = inputValue('separator');
        const date = (function () {
            const now = new Date();
            const m = String(now.getMonth() + 1);
            const d = String(now.getDate());
            return String(now.getFullYear()) + (m.length < 2 ? '0' + m : m) + (d.length < 2 ? '0' + d : d);
        })();
        const shift = checked('reset_policy') === 'shift' ? 'S1' : '';
        const pattern = inputValue('format_pattern') || '{prefix}{separator}{padded}';
        let display = pattern
            .replaceAll('{prefix}', prefix)
            .replaceAll('{dining}', dining)
            .replaceAll('{padded}', padded)
            .replaceAll('{seq}', String(seq))
            .replaceAll('{date}', date)
            .replaceAll('{shift}', shift)
            .replaceAll('{separator}', separator);
        display = collapseSeparators(display, separator) || padded;

        const system = checked('system_id_mode') === 'dated_serial' ? date + '-' + padded : '#' + padded;
        const kitchen = form.querySelector('[name="kitchen_same_as_display"][type="checkbox"]:checked')
            ? display
            : 'K-' + padded;

        const displayEl = root.querySelector('[data-preview="display"]');
        const systemEl = root.querySelector('[data-preview="system"]');
        const kitchenEl = root.querySelector('[data-preview="kitchen"]');
        const sizeWrap = root.querySelector('[data-preview-size]');
        if (displayEl) {
            displayEl.textContent = display;
        }
        if (systemEl) {
            systemEl.textContent = system;
        }
        if (kitchenEl) {
            kitchenEl.textContent = kitchen;
        }
        if (sizeWrap) {
            sizeWrap.setAttribute('data-size', inputValue('display_size') || 'xlarge');
        }
    }

    function applyPreset(name) {
        const preset = presets[name];
        if (!preset) {
            return;
        }
        if (presetInput) {
            presetInput.value = name;
        }
        root.querySelectorAll('[data-apply-preset]').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-apply-preset') === name);
        });
        setRadio('reset_policy', preset.reset_policy);
        setRadio('allocation', preset.allocation);
        setRadio('offline_policy', preset.offline_policy);
        setCheckbox('cycle_enabled', preset.cycle_enabled);
        setCheckbox('show_terminal_in_display', preset.show_terminal_in_display);
        setCheckbox('kitchen_same_as_display', preset.kitchen_same_as_display);
        setValue('cycle_max', preset.cycle_max);
        setValue('padding', preset.padding);
        setValue('start_number', preset.start_number);
        setValue('format_pattern', preset.format_pattern);
        setValue('prefix_style', preset.prefix_style);
        setValue('display_size', preset.display_size);
        refresh();
    }

    function refresh() {
        syncOptionCards();
        toggleDepends();
        updatePreview();
    }

    root.querySelectorAll('[data-apply-preset]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyPreset(btn.getAttribute('data-apply-preset'));
        });
    });

    root.querySelectorAll('[data-insert-token]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const field = form.querySelector('[name="format_pattern"]');
            if (!field) {
                return;
            }
            field.value += btn.getAttribute('data-insert-token');
            if (presetInput) {
                presetInput.value = 'custom';
            }
            refresh();
        });
    });

    form.addEventListener('change', function (evt) {
        if (presetInput && evt.target && !evt.target.closest('[data-apply-preset]')) {
            const name = evt.target.name || '';
            if (name && name !== 'preset') {
                presetInput.value = 'custom';
                root.querySelectorAll('[data-apply-preset]').forEach(function (btn) {
                    btn.classList.remove('is-active');
                });
            }
        }
        refresh();
    });

    form.addEventListener('input', function () {
        updatePreview();
    });

    refresh();
})();
