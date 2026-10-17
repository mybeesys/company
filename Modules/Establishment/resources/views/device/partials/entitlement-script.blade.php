{{-- Shared device-type entitlement binding for add-device modals --}}
<script>
    window.deviceEntitlements = @json($deviceEntitlements ?? ['legacy' => true, 'types' => []]);

    (function () {
        const TYPE_MAP = {
            'kitchen screen': 'kitchen_screen',
            'kitchen_screen': 'kitchen_screen',
            kitchen: 'kitchen_screen',
            cashier: 'cashier',
            pos: 'cashier',
            waiters: 'waiters',
            waiter: 'waiters',
        };

        const labels = {
            kitchen_screen: @json(__('establishment::general.kitchen_screen')),
            cashier: @json(__('establishment::general.cashier')),
            waiters: @json(__('establishment::general.waiters')),
            remaining: @json(__('establishment::general.device_quota_remaining')),
            locked: @json(__('establishment::general.device_module_locked')),
            full: @json(__('establishment::general.device_quota_full')),
            select: @json(__('establishment::general.select_device_type')),
        };

        const optionValues = {
            kitchen_screen: 'kitchen screen',
            cashier: 'cashier',
            waiters: 'waiters',
        };

        function typeState(value) {
            const key = TYPE_MAP[String(value || '').toLowerCase()] || value;
            return (window.deviceEntitlements && window.deviceEntitlements.types)
                ? window.deviceEntitlements.types[key]
                : null;
        }

        function optionLabel(key, state) {
            const base = labels[key] || key;
            if (!state) return base;
            if (!state.module_ok) return base + ' — ' + labels.locked;
            if (!state.allowed && state.quota !== null) return base + ' — ' + labels.full + ` (${state.used}/${state.quota})`;
            if (state.remaining !== null) {
                return base + ' (' + labels.remaining.replace(':count', state.remaining) + ')';
            }
            return base;
        }

        window.applyDeviceEntitlements = function applyDeviceEntitlements(snapshot) {
            if (snapshot) {
                window.deviceEntitlements = snapshot;
            }
            const $select = $('#deviceType');
            if (!$select.length) return;

            const current = $select.val();
            $select.empty();
            $select.append($('<option>', { value: '', disabled: true, selected: true, text: labels.select }));

            Object.keys(optionValues).forEach(function (key) {
                const value = optionValues[key];
                const state = typeState(key);
                const allowed = !state || state.allowed;
                $select.append($('<option>', {
                    value: value,
                    text: optionLabel(key, state),
                    disabled: !allowed,
                }));
            });

            if (current && typeState(current)?.allowed) {
                $select.val(current);
            }
        };

        window.refreshDeviceEntitlements = function refreshDeviceEntitlements() {
            return $.getJSON(@json(url('/devices/entitlements')))
                .done(function (data) {
                    window.applyDeviceEntitlements(data);
                });
        };

        window.notifyDeviceEntitlementError = function notifyDeviceEntitlementError(xhr) {
            const message = xhr?.responseJSON?.message
                || @json(__('messages.something_went_wrong'));
            if (xhr?.responseJSON?.entitlements) {
                window.applyDeviceEntitlements(xhr.responseJSON.entitlements);
            }
            if (typeof toastr !== 'undefined') {
                toastr.error(message);
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: message });
            } else {
                alert(message);
            }
        };

        $(function () {
            window.applyDeviceEntitlements(window.deviceEntitlements);
        });
    })();
</script>
