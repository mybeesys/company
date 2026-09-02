<div class="card card-flush">
    <div class="card-header pt-8">
        <div class="card-title">
            <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
                <li class="nav-item"><a class="nav-link text-active-primary active filter-tab" data-view="all"
                        href="#">{{ __('franchise::lang.all') }}</a></li>
                <li class="nav-item"><a class="nav-link text-active-warning filter-tab" data-view="new_no_contract"
                        href="#">{{ __('franchise::lang.new_no_contract') }}</a></li>
                <li class="nav-item"><a class="nav-link text-active-success filter-tab" data-view="active_contracts"
                        href="#">{{ __('franchise::lang.active_contracts') }}</a></li>
                <li class="nav-item"><a class="nav-link text-active-danger filter-tab" data-view="expired_contracts"
                        href="#">{{ __('franchise::lang.expired_contracts') }}</a></li>
            </ul>
        </div>
        <div class="card-toolbar">
            @dashboardcan(\Modules\Franchise\Support\FranchisePermissions::for('Companies', 'create'))
            <button type="button" class="btn btn-primary" onclick="addCompanyModal()">
                <i class="ki-outline ki-plus fs-2"></i> {{ __('franchise::lang.add_new') }}
            </button>
            @enddashboardcan
        </div>
    </div>
    <table class="table align-middle table-row-dashed fs-6 gy-5" id="companies_table">
        <thead>
            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                <th class="min-w-150px text-start">{{ __('franchise::lang.franchisee_name_ar') }}</th>
                <th class="min-w-100px text-start">{{ __('franchise::lang.city') }}</th>
                <th class="min-w-100px text-start">{{ __('franchise::lang.vat_no') }}</th>
                <th class="min-w-100px text-start">{{ __('franchise::lang.status') }}</th>
                <th class="min-w-100px text-start">{{ __('franchise::lang.mobile') }}</th>
                <th class="text-start min-w-20px">{{ __('franchise::lang.actions') }}</th>
            </tr>
        </thead>
    </table>
</div>

@include('franchise::companies.partials.company-modal')
