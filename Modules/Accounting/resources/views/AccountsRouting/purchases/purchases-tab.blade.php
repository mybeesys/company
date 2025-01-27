<div class="row">
    <div class="col-6">
        <x-accounting::account-routing :section="'purchases_routing'" :title="__('menuItemLang.suppliers')" :typeSelectId="'suppliers_type_route'" :typeSelectName="'suppliers_type_route'"
            :accountSelectId="'suppliers_account'" :accountSelectName="'suppliers_account'" :accounts="$accounts" :typeOptions="$options" />
    </div>
    <div class="col-6">
        <x-accounting::account-routing :section="'purchases_routing'" :title="__('menuItemLang.purchases')" :typeSelectId="'purchases_type_route'" :typeSelectName="'purchases_type_route'"
            :accountSelectId="'purchases_account'" :accountSelectName="'purchases_account'" :accounts="$accounts" :typeOptions="$options" />
    </div>
    <div class="col-6">
        <x-accounting::account-routing :section="'purchases_routing'" :title="__('menuItemLang.purchases-return')" :typeSelectId="'purchases_return_type_route'" :typeSelectName="'purchases_return_type_route'"
            :accountSelectId="'purchases_return_account'" :accountSelectName="'purchases_return_account'" :accounts="$accounts" :typeOptions="$options" />

    </div>
    <div class="col-6">
        <x-accounting::account-routing :section="'purchases_routing'" :title="__('accounting::lang.Earned discount')" :typeSelectId="'discount_purchases_type_route'" :typeSelectName="'discount_purchases_type_route'"
            :accountSelectId="'discount_purchases_account'" :accountSelectName="'discount_purchases_account'" :accounts="$accounts" :typeOptions="$options" />
    </div>
</div>
