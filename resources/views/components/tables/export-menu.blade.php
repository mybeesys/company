@props(['id'])
<div id="kt_{{ $id }}_table_export_menu"
    class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
    data-kt-menu="true">
    <!--begin::Menu item-->
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-kt-export="excel">
            @lang('general.export_as_excel')
        </a>
    </div>
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-kt-export="pdf">
            @lang('general.export_as_pdf')
        </a>
    </div>
    <div class="menu-item px-3">
        <a href="#" class="menu-link px-3" data-kt-export="print">
            @lang('general.print')
        </a>
    </div>
</div>
