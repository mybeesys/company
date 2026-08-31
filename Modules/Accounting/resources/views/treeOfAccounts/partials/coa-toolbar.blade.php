<div class="coa-toolbar d-flex flex-nowrap align-items-center justify-content-between gap-2 mb-4">
    <div class="coa-search-wrap">
        <div class="position-relative">
            <i class="ki-outline ki-magnifier coa-search-icon text-gray-500 position-absolute top-50 translate-middle-y"></i>
            <input type="text" class="form-control form-control-solid form-control-sm coa-search-input" id="accounts_tree_search"
                placeholder="@lang('accounting::lang.coa_search_placeholder')">
        </div>
    </div>
    <div class="d-flex flex-nowrap align-items-center gap-2 coa-toolbar-actions">
        <button type="button" class="btn btn-light-primary btn-sm coa-toolbar-btn" id="expand_all">
            <i class="ki-outline ki-plus-square fs-6 me-1"></i>@lang('accounting::lang.expand_all')
        </button>
        <button type="button" class="btn btn-light btn-sm coa-toolbar-btn" id="collapse_all">
            <i class="ki-outline ki-minus-square fs-6 me-1"></i>@lang('accounting::lang.collapse_all')
        </button>
    </div>
</div>
