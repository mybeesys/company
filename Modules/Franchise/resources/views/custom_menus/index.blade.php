@extends('layouts.app')
@section('title', __('menuItemLang.custom_menus_mgmt'))

@section('content')
    <div class="card mb-7 shadow-sm">
        <div class="card-body">
            <div class="d-flex flex-stack flex-wrap">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-45px me-5">
                        <span class="symbol-label bg-light-primary">
                            <i class="ki-outline ki-setting-3 fs-2x text-primary"></i>
                        </span>
                    </div>
                    <div class="d-flex flex-column">
                        <h2 class="fw-bold text-gray-900 fs-2 mb-0">
                            {{ app()->getLocale() == 'ar' ? 'إدارة قوائم الفرنشايز المخصصة' : 'Franchise Custom Menus Management' }}
                        </h2>
                    </div>
                </div>
                <div class="d-flex gap-3">
                    <button id="save_btn" class="btn btn-primary d-none shadow-sm">
                        <span class="indicator-label">
                            <i class="ki-outline ki-check-circle fs-2 me-2"></i>{{ __('franchise::lang.save') }}
                        </span>
                        <span class="indicator-progress">
                            {{ __('franchise::lang.please_wait') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </div>

            <div class="separator separator-dashed my-5"></div>

            <div class="row align-items-center">
                <div class="col-md-4">
                    <label class="fs-6 fw-bold mb-2">{{ app()->getLocale() == 'ar' ? 'اختيار الشركة' : 'Select Company' }}</label>
                    <select id="franchise_id" class="form-select form-select-solid" data-control="select2" data-placeholder="{{ __('franchise::lang.select_franchise_company') }}">
                        <option></option>
                        @foreach ($franchises as $f)
                            <option value="{{ $f->id }}">{{ app()->getLocale() == 'ar' ? $f->name_ar : $f->name_en }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mt-8 d-none" id="search_container">
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                        <input type="text" id="menu_search" class="form-control form-control-solid ps-13" placeholder="{{ app()->getLocale() == 'ar' ? 'بحث في القوائم...' : 'Search menus...' }}" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="menus_container" class="row d-none"></div>

    <div id="empty_state" class="card shadow-none border-dashed p-20 text-center">
        <div class="card-body">
            <img src="{{ asset('assets/media/illustrations/sigma-1/5.png') }}" class="h-150px mb-5" alt="" />
            <h3 class="text-gray-600 fw-bold">{{ app()->getLocale() == 'ar' ? 'الرجاء اختيار شركة فرنشايز لعرض قوائمها المخصصة' : 'Please select a franchise company to view its custom menus' }}</h3>
        </div>
    </div>

    <div class="modal fade" id="missing_products_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="fw-bold">{{ app()->getLocale() == 'ar' ? 'المنتجات المرتبطة' : 'Linked Products' }}</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <div id="modal_status_area"></div>
                    <ul id="missing_list" class="list-group list-group-flush fs-6 fw-bold text-gray-700"></ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        let menuData = [];

        $('#franchise_id').on('change', function() {
            const id = $(this).val();
            if (!id) return;

            $('#menus_container').html('<div class="col-12 text-center py-10"><span class="spinner-border text-primary"></span></div>').removeClass('d-none');
            $('#empty_state').addClass('d-none');

            $.get(`/franchise/custom-menus/get-data/${id}`, function(data) {
                menuData = data;
                renderMenus(data);
                $('#search_container, #save_btn').removeClass('d-none');
            });
        });

        function renderMenus(data) {
            let html = '';
            if(data.length === 0) {
                html = `<div class="col-12 text-center text-muted py-10">{{ app()->getLocale() == 'ar' ? 'لا توجد قوائم مخصصة' : 'No custom menus found' }}</div>`;
            }

            data.forEach(menu => {
                let statusBadge = '';
                let cardBorder = menu.has_warning ? 'border-warning' : 'border-success';
                let checkColor = menu.has_warning ? 'form-check-warning' : 'form-check-success';

                if (menu.has_warning) {
                    statusBadge = `
                        <div class="badge badge-light-warning fw-bolder cursor-pointer show-missing" data-id="${menu.id}">
                            <i class="ki-outline ki-warning text-warning fs-7 me-1"></i>
                            ${menu.missing_count} {{ app()->getLocale() == 'ar' ? 'غير مفعل' : 'Unpermitted' }}
                        </div>`;
                } else {
                    statusBadge = `<div class="badge badge-light-success fw-bolder">{{ app()->getLocale() == 'ar' ? 'مكتمل' : 'Complete' }}</div>`;
                }

                html += `
                <div class="col-xl-4 col-lg-6 mb-5 menu-card" data-name="${menu.name.toLowerCase()}">
                    <div class="card h-100 border-start border-start-4 ${cardBorder} shadow-sm hover-elevate-up transition-3s">
                        <div class="card-body p-5">
                            <div class="d-flex flex-stack mb-4">
                                <div class="form-check form-check-custom form-check-solid ${checkColor}">
                                    <input class="form-check-input w-25px h-25px menu-checkbox" type="checkbox" value="${menu.id}" ${menu.is_active ? 'checked' : ''} id="menu_${menu.id}">
                                    <label class="form-check-label fw-bolder fs-5 ms-3 text-gray-800 cursor-pointer" for="menu_${menu.id}">
                                        ${menu.name}
                                    </label>
                                </div>
                                ${statusBadge}
                            </div>
                            <div class="d-flex flex-stack bg-light rounded p-3">
                                <div class="d-flex align-items-center me-2">
                                    <i class="ki-outline ki-handcart fs-3 text-primary me-2"></i>
                                    <span class="text-muted fw-bold fs-7">{{ app()->getLocale() == 'ar' ? 'إجمالي المنتجات' : 'Total Products' }}:</span>
                                </div>
                                <span class="text-gray-800 fw-bolder fs-6">${menu.total_items}</span>
                            </div>
                        </div>
                    </div>
                </div>`;
            });
            $('#menus_container').html(html);
        }

        // البحث الفوري
        $('#menu_search').on('keyup', function() {
            let val = $(this).val().toLowerCase();
            $('.menu-card').each(function() {
                $(this).toggle($(this).data('name').includes(val));
            });
        });

        // عرض تفاصيل المنتجات الناقصة
        $(document).on('click', '.show-missing', function() {
            let id = $(this).data('id');
            let menu = menuData.find(m => m.id == id);
            let listHtml = '';

            let statusHtml = `
                <div class="notice d-flex bg-light-danger rounded border-danger border border-dashed mb-5 p-4">
                    <i class="ki-outline ki-information-5 fs-2tx text-danger me-4"></i>
                    <div class="fw-semibold text-gray-700">
                        {{ app()->getLocale() == 'ar' ? 'المنتجات التالية سيتم تفعيل صلاحياتها تلقائياً عند حفظ المنيو.' : 'The following products will be automatically permitted upon saving.' }}
                    </div>
                </div>`;

            menu.missing_items_names.forEach(name => {
                listHtml += `<li class="list-group-item d-flex align-items-center">
                    <span class="bullet bullet-vertical h-20px bg-danger me-5"></span> ${name}
                </li>`;
            });

            $('#modal_status_area').html(statusHtml);
            $('#missing_list').html(listHtml);
            $('#missing_products_modal').modal('show');
        });

        // منطق الحفظ الذكي
        $('#save_btn').on('click', function() {
            let btn = $(this);
            let selectedMenus = $('.menu-checkbox:checked').map(function() { return $(this).val(); }).get();

            // تجميع معرفات وأسماء المنتجات الناقصة في القوائم المختارة فقط
            let allMissingIds = [];
            let allMissingNames = [];

            selectedMenus.forEach(menuId => {
                let menu = menuData.find(m => m.id == menuId);
                if (menu && menu.has_warning) {
                    menu.missing_items_ids.forEach(id => {
                        if(!allMissingIds.includes(id)) allMissingIds.push(id);
                    });
                    menu.missing_items_names.forEach(name => {
                        if(!allMissingNames.includes(name)) allMissingNames.push(name);
                    });
                }
            });

            const submitRequest = (productIdsToGrant = []) => {
                btn.attr('data-kt-indicator', 'on').prop('disabled', true);
                $.post("{{ route('franchise.custom_menus.update') }}", {
                    _token: "{{ csrf_token() }}",
                    franchise_id: $('#franchise_id').val(),
                    menu_ids: selectedMenus,
                    products_to_grant: productIdsToGrant
                }, function(res) {
                    btn.removeAttr('data-kt-indicator').prop('disabled', false);
                    Swal.fire({
                        text: res.message,
                        icon: "success",
                        confirmButtonText: "{{ app()->getLocale() == 'ar' ? 'حسناً' : 'OK' }}",
                        customClass: { confirmButton: "btn btn-primary" }
                    }).then(() => {
                        // إعادة تحميل البيانات لتحديث الحالات والعدادات
                        $('#franchise_id').trigger('change');
                    });
                }).fail(function() {
                    btn.removeAttr('data-kt-indicator').prop('disabled', false);
                    Swal.fire("Error", "Something went wrong", "error");
                });
            };

            // التحقق من وجود نواقص قبل الحفظ
            if (allMissingIds.length > 0) {
                let namesHtml = '<div class="text-start mt-4 p-3 bg-light rounded" style="max-height:150px; overflow-y:auto;"><ul class="fs-7 text-gray-700">';
                allMissingNames.forEach(n => namesHtml += `<li>${n}</li>`);
                namesHtml += '</ul></div>';

                Swal.fire({
                    title: "{{ app()->getLocale() == 'ar' ? 'تأكيد منح الصلاحيات' : 'Confirm Granting Permissions' }}",
                    html: `{{ app()->getLocale() == 'ar' ? 'هذه القوائم تحتوي على منتجات غير مفعلة للفرنشايز. هل توافق على منح صلاحية بيعها تلقائياً؟' : 'These menus contain products without franchise permission. Do you agree to grant them automatically?' }} ${namesHtml}`,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "{{ app()->getLocale() == 'ar' ? 'نعم، حفظ ومنح' : 'Yes, Save & Grant' }}",
                    cancelButtonText: "{{ app()->getLocale() == 'ar' ? 'إلغاء' : 'Cancel' }}",
                    customClass: { confirmButton: "btn btn-primary", cancelButton: "btn btn-active-light" }
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitRequest(allMissingIds);
                    }
                });
            } else {
                submitRequest([]);
            }
        });
    });
</script>

<style>
    .transition-3s { transition: all 0.3s ease; }
    .border-start-4 { border-left-width: 4px !important; }
    [dir="rtl"] .border-start-4 { border-right-width: 4px !important; border-left-width: 0 !important; }
    .hover-elevate-up:hover { transform: translateY(-5px); }
    .form-check-warning .form-check-input:checked { background-color: #ffc107; border-color: #ffc107; }
</style>
@endsection
