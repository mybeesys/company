<div class="row">
    <div class="col-6">
        <x-accounting::account-routing :section="'sales_routing'" :title="__('sales::lang.clients')" :typeSelectId="'client_type_route'" :typeSelectName="'client_type_route'"
        :accountSelectId="'client_account'" :accountSelectName="'client_account'" :accounts="$accounts" :typeOptions="$options" />

    </div>
    <div class="col-6">
        <x-accounting::account-routing :section="'sales_routing'" :title="__('sales::lang.sales')" :typeSelectId="'sales_type_route'" :typeSelectName="'sales_type_route'"
            :accountSelectId="'sales_account'" :accountSelectName="'sales_account'" :accounts="$accounts" :typeOptions="$options" />
    </div>
    <div class="col-6">
        <x-accounting::account-routing :section="'sales_routing'" :title="__('menuItemLang.sell-return')" :typeSelectId="'sell_return_type_route'" :typeSelectName="'sell_return_type_route'"
        :accountSelectId="'sell_return_account'" :accountSelectName="'sell_return_account'" :accounts="$accounts" :typeOptions="$options" />

    </div>
    <div class="col-6">
        <x-accounting::account-routing :section="'sales_routing'" :title="__('accounting::lang.Discount allowed')" :typeSelectId="'discount_sales_type_route'" :typeSelectName="'discount_sales_type_route'"
        :accountSelectId="'discount_sales_account'" :accountSelectName="'discount_sales_account'" :accounts="$accounts" :typeOptions="$options" />
    </div>
</div>
