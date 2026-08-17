<script>
    window.accountsRoutingSelect2Config = {
        tabs: {
            '#accounts-routing-sales-tab': [
                '#sales_client_account', '#sales_client_type_route', '#sales_sales_type_route',
                '#sales_sales_account', '#sales_sell_return_type_route', '#sales_sell_return_account',
                '#sales_discount_sales_account', '#sales_discount_sales_type_route',
                '#sales_vat_calculation_account', '#sales_vat_calculation_type_route',
                '#sales_amount_before_vat_type_route', '#sales_amount_before_vat_account',
                '#sales_discount_calculation_type_route', '#sales_discount_calculation_account',
                '#sales_discount_allowed_type_route', '#sales_discount_allowed_account',
                '#sales_total_amount_account', '#sales_total_amount_type_route',
            ],
            '#accounts-routing-purchases-tab': [
                '#purchases_suppliers_type_route', '#purchases_purchases_account',
                '#purchases_purchases_type_route', '#purchases_suppliers_account',
                '#purchases_purchases_return_type_route', '#purchases_purchases_return_account',
                '#purchases_discount_purchases_type_route', '#purchases_discount_purchases_account',
                '#purchases_vat_calculation_type_route', '#purchases_vat_calculation_account',
                '#purchases_total_amount_type_route', '#purchases_total_amount_account',
                '#purchases_amount_before_vat_type_route', '#purchases_amount_before_vat_account',
                '#purchases_discount_calculation_type_route', '#purchases_discount_calculation_account',
                '#purchases_purchase_type_route', '#purchases_purchase_account',
                '#purchases_purchase_return_type_route', '#purchases_purchase_return_account',
                '#purchases_earned_discount_type_route', '#purchases_earned_discount_account',
            ],
            '#accounts-routing-periodic-inventory-tab': [
                '#perpetual_inventory_asset_account',
                '#perpetual_inventory_cogs_account',
                '#periodic_inventory_adjustment_type_route',
                '#periodic_inventory_adjustment_account',
            ],
            '#accounts-routing-fiscal-close-tab': [
                '#fiscal_close_current_period_result_account',
                '#fiscal_close_retained_earnings_account',
            ],
        },
    };

    function initAccountsRoutingSelect2InPane(paneSelector) {
        const pane = paneSelector || '#accounts-routing-sales-tab';
        const $pane = $(pane);
        if (!$pane.length) {
            return;
        }

        const selectors = (window.accountsRoutingSelect2Config.tabs[pane] || []);
        const $dropdownParent = $('#accounts_routing_settings_tab');

        selectors.forEach(function (selector) {
            const $el = $pane.find(selector);
            if (!$el.length) {
                return;
            }

            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }

            $el.select2({
                dropdownParent: $dropdownParent,
                width: '100%',
                placeholder: $el.find('option[value=""]').first().text() || undefined,
                allowClear: true,
            });
        });
    }

    function initAccountsRoutingSelect2() {
        const activeSubTab = document.querySelector('#accountsRoutingSubTabContent .tab-pane.active.show')
            || document.querySelector('#accountsRoutingSubTabContent .tab-pane.active');

        if (activeSubTab?.id) {
            initAccountsRoutingSelect2InPane('#' + activeSubTab.id);
            return;
        }

        initAccountsRoutingSelect2InPane('#accounts-routing-sales-tab');
    }

    function activateAccountsRoutingSubTabFromHash() {
        const hash = window.location.hash || '';
        if (!hash.startsWith('#accounts-routing-')) {
            return false;
        }

        const trigger = document.querySelector('a[href="' + hash + '"][data-bs-toggle="tab"]');
        if (!trigger) {
            return false;
        }

        bootstrap.Tab.getOrCreateInstance(trigger).show();
        return true;
    }

    $(document).ready(function () {
        const routingTab = document.querySelector('a[href="#accounts_routing_settings_tab"]');
        const routingPane = document.getElementById('accounts_routing_settings_tab');

        document.querySelectorAll('a[data-bs-toggle="tab"][href^="#accounts-routing-"]').forEach(function (subTab) {
            subTab.addEventListener('shown.bs.tab', function () {
                const paneSelector = this.getAttribute('href');
                if (paneSelector) {
                    initAccountsRoutingSelect2InPane(paneSelector);
                    if (history.replaceState) {
                        const url = new URL(window.location.href);
                        url.hash = paneSelector.replace('#', '');
                        history.replaceState(null, '', url.toString());
                    }
                }
            });
        });

        const openedFromHash = activateAccountsRoutingSubTabFromHash();

        if (routingPane?.classList.contains('active') || routingPane?.classList.contains('show')) {
            if (openedFromHash && window.location.hash) {
                initAccountsRoutingSelect2InPane(window.location.hash);
            } else {
                initAccountsRoutingSelect2();
            }
        }

        if (routingTab) {
            routingTab.addEventListener('shown.bs.tab', function () {
                if (window.location.hash && window.location.hash.startsWith('#accounts-routing-')) {
                    activateAccountsRoutingSubTabFromHash();
                    initAccountsRoutingSelect2InPane(window.location.hash);
                } else {
                    initAccountsRoutingSelect2();
                }
            });
        }

        window.addEventListener('hashchange', function () {
            if (!window.location.hash.startsWith('#accounts-routing-')) {
                return;
            }
            if (activateAccountsRoutingSubTabFromHash()) {
                initAccountsRoutingSelect2InPane(window.location.hash);
            }
        });
    });
</script>
