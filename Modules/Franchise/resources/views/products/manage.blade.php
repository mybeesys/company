@extends('layouts.app')
@section('title', __('franchise::lang.manage_franchise_products'))

@section('content')
    @php
        $productsData = [];
        foreach ($categories as $cat) {
            if ($cat->subcategories) {
                foreach ($cat->subcategories as $sub) {
                    if ($sub->products) {
                        foreach ($sub->products as $prod) {
                            $productsData[$prod->id] = [
                                'ingredients' => $prod->ingredients ? $prod->ingredients->pluck('id')->toArray() : [],
                                'modifiers' => $prod->modifiers
                                    ? $prod->modifiers->pluck('modifier_class_id')->toArray()
                                    : [],
                                'attributes' => $prod->attributeClasses
                                    ? $prod->attributeClasses->pluck('id')->toArray()
                                    : [],
                            ];
                        }
                    }
                }
            }
        }
    @endphp

    <div class="card card-flush mb-5">
        <div class="card-body">
            <div class="d-flex flex-stack flex-wrap">
                <div class="d-flex align-items-center me-3">
                    <div class="d-flex flex-column">
                        <h1 class="text-gray-900 fw-bold fs-2 mb-2">{{ __('franchise::lang.manage_franchise_products') }}
                        </h1>
                        <div class="d-flex align-items-center">
                            <select id="franchise_select" class="form-select form-select-solid w-300px" data-control="select2"
                                data-placeholder="{{ __('franchise::lang.select_franchise_company') }}">
                                <option></option>
                                @foreach ($franchises as $franchise)
                                    <option value="{{ $franchise->id }}">
                                        {{ app()->getLocale() == 'ar' ? $franchise->name_ar : $franchise->name_en }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="align-items-center gap-2" id="global_controls" style="display: none !important;">
                    <div class="d-flex flex-column align-items-end me-5">
                        <span
                            class="text-muted fw-bold fs-7 mb-1">{{ app()->getLocale() == 'ar' ? 'إجمالي التحديد' : 'Overall Selection' }}</span>
                        <span class="text-primary fw-bolder fs-4" id="overall_count">0/0</span>
                    </div>
                    <button type="button" class="btn btn-light-primary btn-sm"
                        onclick="toggleAllAccordions(true)">{{ app()->getLocale() == 'ar' ? 'فتح الكل' : 'Expand All' }}</button>
                    <button type="button" class="btn btn-light-danger btn-sm"
                        onclick="toggleAllAccordions(false)">{{ app()->getLocale() == 'ar' ? 'إغلاق الكل' : 'Collapse All' }}</button>
                    <button type="button" class="btn btn-primary" id="save_permissions" disabled>
                        <span class="indicator-label"><i class="ki-outline ki-check fs-2"></i>
                            {{ __('franchise::lang.save') }}</span>
                        <span class="indicator-progress"><span
                                class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </div>

            <div id="overall_progress_container" style="display: none;" class="mt-5">
                <div class="progress h-8px w-100 bg-light-primary">
                    <div id="overall_progress_bar" class="progress-bar bg-primary" role="progressbar" style="width: 0%;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header border-0 pt-5">
            <h3 class="card-title align-items-start flex-column">
                <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
                    <li class="nav-item">
                        <a class="nav-link text-active-primary active" data-bs-toggle="tab" href="#tab_products">
                            <i
                                class="ki-outline ki-basket fs-4 me-2"></i>{{ app()->getLocale() == 'ar' ? 'المنتجات' : 'Products' }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-active-success" data-bs-toggle="tab" href="#tab_approvals">
                            <i
                                class="ki-outline ki-verify fs-4 me-2"></i>{{ app()->getLocale() == 'ar' ? 'طلبات القبول' : 'Approvals' }}
                            <span class="badge badge-circle badge-danger ms-2" id="approval_badge"
                                style="display:none">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-active-primary" data-bs-toggle="tab" href="#tab_ingredients">
                            <i
                                class="ki-outline ki-layer fs-4 me-2"></i>{{ app()->getLocale() == 'ar' ? 'المكونات' : 'Ingredients' }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-active-primary" data-bs-toggle="tab" href="#tab_modifiers">
                            <i
                                class="ki-outline ki-setting-4 fs-4 me-2"></i>{{ app()->getLocale() == 'ar' ? 'الإضافات' : 'Modifiers' }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-active-primary" data-bs-toggle="tab" href="#tab_attributes">
                            <i
                                class="ki-outline ki-dna fs-4 me-2"></i>{{ app()->getLocale() == 'ar' ? 'المتغيرات' : 'Attributes' }}
                        </a>
                    </li>
                </ul>
            </h3>
        </div>

        <div class="card-body pt-0">
            <div id="main_content_area" style="display: none;">
                <div class="tab-content" id="main_franchise_tabs">
                    <div class="tab-pane fade show active" id="tab_products" role="tabpanel">
                        <div class="accordion accordion-icon-toggle" id="products_accordion">
                            @foreach ($categories as $cat)
                                @if ($cat->subcategories->count() > 0)
                                    <div class="accordion-item mb-5 border rounded main-cat-item">
                                        <div class="accordion-header d-flex align-items-center bg-light">
                                            <div class="form-check form-check-custom form-check-solid ms-5 me-2">
                                                <input class="form-check-input select-all-main" type="checkbox"
                                                    data-cat-id="{{ $cat->id }}" />
                                            </div>
                                            <button class="accordion-button fs-4 fw-bold collapsed py-5" type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#collapse_cat_{{ $cat->id }}">
                                                <div class="d-flex justify-content-between align-items-center w-100 me-5">
                                                    <span>{{ app()->getLocale() == 'ar' ? $cat->name_ar : $cat->name_en }}</span>
                                                    <span class="badge badge-light-danger fs-7 main-counter"
                                                        id="main_counter_{{ $cat->id }}">0 / 0</span>
                                                </div>
                                            </button>
                                        </div>
                                        <div id="collapse_cat_{{ $cat->id }}" class="accordion-collapse collapse">
                                            <div class="accordion-body border-top p-5">
                                                <div class="accordion accordion-icon-toggle"
                                                    id="sub_accordion_{{ $cat->id }}">
                                                    @foreach ($cat->subcategories as $sub)
                                                        @if ($sub->products->count() > 0)
                                                            <div class="accordion-item mb-3 border rounded">
                                                                <div
                                                                    class="accordion-header d-flex align-items-center bg-white shadow-sm">
                                                                    <div
                                                                        class="form-check form-check-custom form-check-solid ms-5 me-2">
                                                                        <input class="form-check-input select-all-sub"
                                                                            type="checkbox"
                                                                            data-sub-id="{{ $sub->id }}" />
                                                                    </div>
                                                                    <button
                                                                        class="accordion-button fs-6 fw-semibold collapsed py-3"
                                                                        data-bs-toggle="collapse"
                                                                        data-bs-target="#sub_collapse_{{ $sub->id }}">
                                                                        <div
                                                                            class="d-flex justify-content-between align-items-center w-100 me-5">
                                                                            <span>{{ app()->getLocale() == 'ar' ? $sub->name_ar : $sub->name_en }}</span>
                                                                            <span
                                                                                class="badge badge-light-info fs-8 sub-counter"
                                                                                id="sub_counter_{{ $sub->id }}">0 /
                                                                                {{ $sub->products->count() }}</span>
                                                                        </div>
                                                                    </button>
                                                                </div>
                                                                <div id="sub_collapse_{{ $sub->id }}"
                                                                    class="accordion-collapse collapse">
                                                                    <div class="accordion-body row g-3">
                                                                        @foreach ($sub->products as $product)
                                                                            <div class="col-md-3">
                                                                                <div
                                                                                    class="form-check form-check-custom form-check-solid p-2">
                                                                                    <input
                                                                                        class="form-check-input perm-check main-child-{{ $cat->id }} sub-child-{{ $sub->id }}"
                                                                                        type="checkbox"
                                                                                        value="{{ $product->id }}"
                                                                                        data-type="product"
                                                                                        id="prod_{{ $product->id }}" />
                                                                                    <label class="form-check-label fs-7"
                                                                                        for="prod_{{ $product->id }}">{{ app()->getLocale() == 'ar' ? $product->name_ar : $product->name_en }}</label>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab_approvals" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                                <thead>
                                    <tr class="fw-bold text-muted bg-light">
                                        <th class="ps-4 min-w-200px rounded-start">
                                            {{ app()->getLocale() == 'ar' ? 'المنتج' : 'Product' }}</th>
                                        <th class="min-w-150px">{{ app()->getLocale() == 'ar' ? 'التصنيف' : 'Category' }}
                                        </th>
                                        <th class="min-w-100px">{{ app()->getLocale() == 'ar' ? 'السعر' : 'Price' }}</th>
                                        <th class="min-w-100px text-end rounded-end pe-4">
                                            {{ app()->getLocale() == 'ar' ? 'الإجراء' : 'Actions' }}</th>
                                    </tr>
                                </thead>
                                <tbody id="approval_requests_table"></tbody>
                            </table>
                        </div>
                        <div id="no_approvals_msg" class="text-center py-10" style="display:none">
                            <span
                                class="text-muted">{{ app()->getLocale() == 'ar' ? 'لا توجد طلبات معلقة لهذا الفرع' : 'No pending requests for this franchise' }}</span>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab_ingredients" role="tabpanel">
                        <div class="row g-5">
                            @foreach ($ingredients as $ing)
                                <div class="col-md-3">
                                    <div class="form-check form-check-custom form-check-solid p-4 border rounded bg-light">
                                        <input class="form-check-input perm-check" type="checkbox"
                                            value="{{ $ing->id }}" data-type="ingredient"
                                            id="ing_{{ $ing->id }}" />
                                        <label class="form-check-label fw-bold"
                                            for="ing_{{ $ing->id }}">{{ app()->getLocale() == 'ar' ? $ing->name_ar : $ing->name_en }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab_modifiers" role="tabpanel">
                        <div class="accordion accordion-icon-toggle" id="modifiers_accordion">
                            @foreach ($modifierClasses as $mClass)
                                <div class="accordion-item mb-5 border rounded">
                                    <div class="accordion-header d-flex align-items-center bg-light">
                                        <div class="form-check form-check-custom form-check-solid ms-5 me-2">
                                            <input class="form-check-input select-all-mod-class" type="checkbox"
                                                data-class-id="{{ $mClass->id }}" />
                                        </div>
                                        <button class="accordion-button fs-5 fw-bold collapsed py-5"
                                            data-bs-toggle="collapse" data-bs-target="#mod_class_{{ $mClass->id }}">
                                            <div class="d-flex justify-content-between align-items-center w-100 me-5">
                                                <span>{{ app()->getLocale() == 'ar' ? $mClass->name_ar : $mClass->name_en }}</span>
                                                <span class="badge badge-light-primary mod-counter"
                                                    id="mod_counter_{{ $mClass->id }}">0 /
                                                    {{ $mClass->children->count() }}</span>
                                            </div>
                                        </button>
                                    </div>
                                    <div id="mod_class_{{ $mClass->id }}" class="accordion-collapse collapse">
                                        <div class="accordion-body row g-4">
                                            @foreach ($mClass->children as $mod)
                                                <div class="col-md-3">
                                                    <div class="form-check form-check-custom form-check-solid">
                                                        <input
                                                            class="form-check-input perm-check mod-child-{{ $mClass->id }}"
                                                            type="checkbox" value="{{ $mod->id }}"
                                                            data-type="modifier" id="mod_{{ $mod->id }}" />
                                                        <label class="form-check-label"
                                                            for="mod_{{ $mod->id }}">{{ app()->getLocale() == 'ar' ? $mod->name_ar : $mod->name_en }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab_attributes" role="tabpanel">
                        <div class="accordion accordion-icon-toggle" id="attributes_accordion">
                            @foreach ($attributeClasses as $aClass)
                                <div class="accordion-item mb-5 border rounded">
                                    <div class="accordion-header d-flex align-items-center bg-light">
                                        <div class="form-check form-check-custom form-check-solid ms-5 me-2">
                                            <input class="form-check-input select-all-attr-class" type="checkbox"
                                                data-class-id="{{ $aClass->id }}" />
                                        </div>
                                        <button class="accordion-button fs-5 fw-bold collapsed py-5"
                                            data-bs-toggle="collapse" data-bs-target="#attr_class_{{ $aClass->id }}">
                                            <div class="d-flex justify-content-between align-items-center w-100 me-5">
                                                <span>{{ app()->getLocale() == 'ar' ? $aClass->name_ar : $aClass->name_en }}</span>
                                                <span class="badge badge-light-primary attr-counter"
                                                    id="attr_counter_{{ $aClass->id }}">0 /
                                                    {{ $aClass->children->count() }}</span>
                                            </div>
                                        </button>
                                    </div>
                                    <div id="attr_class_{{ $aClass->id }}" class="accordion-collapse collapse">
                                        <div class="accordion-body row g-4">
                                            @foreach ($aClass->children as $attr)
                                                <div class="col-md-3">
                                                    <div class="form-check form-check-custom form-check-solid">
                                                        <input
                                                            class="form-check-input perm-check attr-child-{{ $aClass->id }}"
                                                            type="checkbox" value="{{ $attr->id }}"
                                                            data-type="attribute" id="attr_{{ $attr->id }}" />
                                                        <label class="form-check-label"
                                                            for="attr_{{ $attr->id }}">{{ app()->getLocale() == 'ar' ? $attr->name_ar : $attr->name_en }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div id="empty_state" class="text-center py-20">
                <i class="ki-outline ki-information-2 fs-4x text-gray-300"></i>
                <h3 class="text-gray-400 mt-5">
                    {{ app()->getLocale() == 'ar' ? 'يرجى اختيار شركة فرنشايز لبدء إدارة الصلاحيات والطلبات' : 'Please select a franchise company to manage permissions and approvals' }}
                </h3>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        const productsRelationData = @json($productsData);

        $(document).ready(function() {
            $('#franchise_select').on('change', function() {
                const id = $(this).val();
                if (!id) {
                    $('#global_controls, #overall_progress_container, #main_content_area').hide();
                    $('#empty_state').show();
                    return;
                }
                loadPermissions(id);
                loadApprovalRequests(id);
            });

            function loadPermissions(id) {
                $('.perm-check, .form-check-input').prop('checked', false);
                $.ajax({
                    url: `/franchise/products/permissions/${id}`,
                    method: 'GET',
                    success: function(data) {
                        $('#empty_state').hide();
                        $('#global_controls').css('display', 'flex').attr('style',
                            'display: flex !important');
                        $('#overall_progress_container').show();
                        $('#main_content_area').show();
                        $('#save_permissions').prop('disabled', false);

                        if (data.product) data.product.forEach(v => $(`#prod_${v}`).prop('checked',
                            true));
                        if (data.ingredient) data.ingredient.forEach(v => $(`#ing_${v}`).prop('checked',
                            true));
                        if (data.modifier) data.modifier.forEach(v => $(`#mod_${v}`).prop('checked',
                            true));
                        if (data.attribute) data.attribute.forEach(v => $(`#attr_${v}`).prop('checked',
                            true));

                        updateAllUI();
                    }
                });
            }

            function loadApprovalRequests(id) {
                $.ajax({
                    url: `/franchise/products/pending/${id}`,
                    method: 'GET',
                    success: function(products) {
                        let html = '';
                        if (products.length > 0) {
                            $('#approval_badge').text(products.length).show();
                            $('#no_approvals_msg').hide();
                            products.forEach(p => {
                                // التحقق من وجود الصورة أو وضع الصورة الافتراضية
                                // ملاحظة: المسارات في الويب تستخدم / وليس \
                                const productImage = p.image ? `/uploads/img/${p.image}` :
                                    '/images.png';

                                // جلب اسم التصنيف (إذا كان موجوداً في الاستجابة) أو وضع "غير محدد"
                                const categoryName = p.category ? (document.documentElement
                                        .lang == 'ar' ? p.category.name_ar : p.category.name_en
                                        ) : '---';

                                html += `
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-5">
                                        <img src="${productImage}" onerror="this.src='/images.png'" />
                                    </div>
                                    <div class="d-flex justify-content-start flex-column">
                                        <span class="text-dark fw-bold fs-6">${p.name_ar}</span>
                                        <span class="text-muted fw-semibold fs-7 d-block">SKU: ${p.SKU || '---'}</span>
                                        <span class="text-muted fw-semibold fs-7">${p.name_en}</span>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-light-info">${categoryName}</span></td>
                            <td><span class="fw-bold">${p.price}</span></td>
                            <td class="text-end pe-4">
                                <button onclick="handleAction(${p.id}, 'approve')" class="btn btn-icon btn-bg-light btn-active-color-success btn-sm me-1">
                                    <i class="ki-outline ki-check fs-3"></i>
                                </button>
                                <button onclick="handleAction(${p.id}, 'reject')" class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm">
                                    <i class="ki-outline ki-cross fs-3"></i>
                                </button>
                            </td>
                        </tr>`;
                            });
                        } else {
                            $('#approval_badge').hide();
                            $('#no_approvals_msg').show();
                        }
                        $('#approval_requests_table').html(html);
                    }
                });
            }

            window.handleAction = function(productId, action) {
                if (action === 'reject') {
                    Swal.fire({
                        title: 'سبب الرفض',
                        input: 'text',
                        showCancelButton: true,
                        confirmButtonText: 'إرسال الرفض'
                    }).then((result) => {
                        if (result.isConfirmed) submitApprovalStatus(productId, action, result.value);
                    });
                } else {
                    submitApprovalStatus(productId, action);
                }
            };

            function submitApprovalStatus(id, action, reason = '') {
                $.post(`/franchise/products/approve-action`, {
                    _token: "{{ csrf_token() }}",
                    product_id: id,
                    status: action,
                    reason: reason
                }, function(res) {
                    Swal.fire('تم التحديث', res.message, 'success');
                    loadApprovalRequests($('#franchise_select').val());
                });
            }

            $(document).on('change', '.perm-check[data-type="product"]', function() {
                if ($(this).is(':checked')) {
                    const productId = $(this).val();
                    const relations = productsRelationData[productId];
                    if (relations) {
                        relations.ingredients.forEach(id => $(`#ing_${id}`).prop('checked', true));
                        relations.modifiers.forEach(classId => $(`.mod-child-${classId}`).prop('checked',
                            true));
                        relations.attributes.forEach(classId => $(`.attr-child-${classId}`).prop('checked',
                            true));
                    }
                }
                updateAllUI();
            });

            $(document).on('change', '.perm-check', function() {
                updateAllUI();
            });

            $(document).on('change', '.select-all-main', function(e) {
                e.stopPropagation();
                const id = $(this).data('cat-id');
                $(`.main-child-${id}`).prop('checked', $(this).is(':checked')).trigger('change');
            });

            $(document).on('change', '.select-all-sub', function(e) {
                e.stopPropagation();
                const id = $(this).data('sub-id');
                $(`.sub-child-${id}`).prop('checked', $(this).is(':checked')).trigger('change');
            });

            $(document).on('change', '.select-all-mod-class', function(e) {
                e.stopPropagation();
                const id = $(this).data('class-id');
                $(`.mod-child-${id}`).prop('checked', $(this).is(':checked'));
                updateAllUI();
            });

            $(document).on('change', '.select-all-attr-class', function(e) {
                e.stopPropagation();
                const id = $(this).data('class-id');
                $(`.attr-child-${id}`).prop('checked', $(this).is(':checked'));
                updateAllUI();
            });

            $('#save_permissions').on('click', function() {
                const btn = $(this);
                let permissions = [];
                $('.perm-check:checked').each(function() {
                    permissions.push({
                        id: $(this).val(),
                        type: $(this).data('type')
                    });
                });
                btn.attr('data-kt-indicator', 'on').prop('disabled', true);
                $.ajax({
                    url: "{{ route('franchise.products.update') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        franchise_id: $('#franchise_select').val(),
                        permissions: permissions
                    },
                    success: function(res) {
                        btn.removeAttr('data-kt-indicator').prop('disabled', false);
                        Swal.fire({
                            text: res.message,
                            icon: "success"
                        });
                    },
                    error: function() {
                        btn.removeAttr('data-kt-indicator').prop('disabled', false);
                        Swal.fire({
                            text: "Error",
                            icon: "error"
                        });
                    }
                });
            });
        });

        function updateAllUI() {
            let totalAll = $('.perm-check').length;
            let selectedAll = $('.perm-check:checked').length;
            $('#overall_count').text(`${selectedAll} / ${totalAll}`);
            $('#overall_progress_bar').css('width', (totalAll > 0 ? (selectedAll / totalAll) * 100 : 0) + '%');

            $('.select-all-main').each(function() {
                let id = $(this).data('cat-id');
                let t = $(`.main-child-${id}`).length;
                let s = $(`.main-child-${id}:checked`).length;
                $(`#main_counter_${id}`).text(`${s} / ${t}`).toggleClass('badge-light-success', s === t && t > 0)
                    .toggleClass('badge-light-danger', s === 0);
                $(this).prop('checked', s === t && t > 0);
            });

            $('.select-all-sub').each(function() {
                let id = $(this).data('sub-id');
                let t = $(`.sub-child-${id}`).length;
                let s = $(`.sub-child-${id}:checked`).length;
                $(`#sub_counter_${id}`).text(`${s} / ${t}`).toggleClass('badge-light-success', s === t && t > 0)
                    .toggleClass('badge-light-danger', s === 0);
                $(this).prop('checked', s === t && t > 0);
            });

            $('.select-all-mod-class').each(function() {
                let id = $(this).data('class-id');
                let t = $(`.mod-child-${id}`).length;
                let s = $(`.mod-child-${id}:checked`).length;
                $(`#mod_counter_${id}`).text(`${s} / ${t}`).toggleClass('badge-light-success', s === t && t > 0)
                    .toggleClass('badge-light-danger', s === 0);
                $(this).prop('checked', s === t && t > 0);
            });

            $('.select-all-attr-class').each(function() {
                let id = $(this).data('class-id');
                let t = $(`.attr-child-${id}`).length;
                let s = $(`.attr-child-${id}:checked`).length;
                $(`#attr_counter_${id}`).text(`${s} / ${t}`).toggleClass('badge-light-success', s === t && t > 0)
                    .toggleClass('badge-light-danger', s === 0);
                $(this).prop('checked', s === t && t > 0);
            });
        }

        function toggleAllAccordions(expand) {
            if (expand) {
                $('.accordion-collapse').addClass('show');
                $('.accordion-button').removeClass('collapsed').attr('aria-expanded', "true");
            } else {
                $('.accordion-collapse').removeClass('show');
                $('.accordion-button').addClass('collapsed').attr('aria-expanded', "false");
            }
        }
    </script>
@endsection
