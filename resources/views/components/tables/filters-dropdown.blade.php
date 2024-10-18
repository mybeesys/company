<div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
        data-kt-menu-placement="{{ session()->get('locale') == 'ar' ? 'bottom-start' : 'bottom-end' }}">
        <i class="ki-outline ki-filter fs-2"></i>@lang('general.filters')
    </button>

    <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">

        <div class="px-7 py-5">
            <div class="fs-5 text-gray-900 fw-bold">@lang('general.filters')</div>
        </div>
        <div class="separator border-gray-200"></div>

        <div class="px-7 py-5" data-kt-filter="form">
            {{ $slot }}
            <div class="d-flex justify-content-end">
                <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                    data-kt-menu-dismiss="true" data-kt-filter="reset">@lang('general.reset')</button>
                <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                    data-kt-filter="filter">@lang('general.apply')</button>
            </div>
        </div>
    </div>
</div>
