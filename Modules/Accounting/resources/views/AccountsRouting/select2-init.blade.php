<script>
    function initAccountsRoutingSelect2() {
        if (window.__accountsRoutingSelect2Initialized) {
            return;
        }
        window.__accountsRoutingSelect2Initialized = true;

        const $root = $('#accounts_routing_settings_tab');
        const selects = [
            '#sales_client_account', '#sales_client_type_route', '#sales_sales_type_route',
            '#sales_sales_account', '#sales_sell_return_type_route', '#sales_sell_return_account',
            '#sales_discount_sales_account', '#sales_discount_sales_type_route',
            '#purchases_suppliers_type_route', '#purchases_purchases_account',
            '#purchases_purchases_type_route', '#purchases_suppliers_account',
            '#purchases_purchases_return_type_route', '#purchases_purchases_return_account',
            '#purchases_discount_purchases_type_route', '#purchases_discount_purchases_account',
            '#purchases_vat_calculation_type_route', '#purchases_vat_calculation_account',
            '#purchases_total_amount_type_route', '#purchases_total_amount_account',
            '#sales_amount_before_vat_type_route', '#purchases_amount_before_vat_account',
            '#purchases_discount_calculation_type_route', '#purchases_discount_calculation_account',
            '#sales_total_amount_account', '#purchases_purchase_type_route', '#purchases_purchase_account',
            '#purchases_amount_before_vat_type_route', '#purchases_purchase_return_type_route',
            '#purchases_purchase_return_account', '#sales_discount_calculation_type_route',
            '#sales_vat_calculation_account', '#purchases_earned_discount_type_route',
            '#purchases_earned_discount_account', '#sales_total_amount_type_route',
            '#sales_vat_calculation_type_route', '#sales_amount_before_vat_type_route',
            '#sales_amount_before_vat_account', '#sales_discount_calculation_account',
            '#sales_discount_allowed_type_route', '#sales_discount_allowed_account',
            '#periodic_inventory_adjustment_type_route', '#periodic_inventory_adjustment_account',
        ];

        selects.forEach(function (selector) {
            const $el = $root.find(selector);
            if ($el.length && !$el.hasClass('select2-hidden-accessible')) {
                $el.select2({ dropdownParent: $root });
            }
        });
    }

    $(document).ready(function () {
        const routingTab = document.querySelector('a[href="#accounts_routing_settings_tab"]');
        const routingPane = document.getElementById('accounts_routing_settings_tab');

        if (routingPane?.classList.contains('active') || routingPane?.classList.contains('show')) {
            initAccountsRoutingSelect2();
        }

        if (routingTab) {
            routingTab.addEventListener('shown.bs.tab', function () {
                initAccountsRoutingSelect2();
            });
        }
    });
</script>
