@extends('sales::layouts.master')

@section('title', __('menuItemLang.coupons'))

@section('css')
    @parent
    <style>
        .hover-primary:hover {
            color: #0d6efd !important;
        }
    </style>
@endsection
@section('content')
    <div class="d-flex flex-column flex-row-fluid gap-5">
        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold">
            <li class="nav-item">
                <a class="nav-link nav-link-coupon justify-content-center text-active-gray-800 active" data-bs-toggle="tab"
                    href="#coupons_tab">@lang('menuItemLang.coupons')</a>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="coupons_tab" role="tabpanel">
                <x-cards.card>
                    <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
                        <x-tables.table-header :search="false" model="coupon" module="sales">
                            <x-slot:filters>
                                <x-tables.filters-dropdown submitButtonClass="coupon_submit_button"
                                    resetButtonClass="coupon_reset_button">
                                    <x-sales::coupons.filters />
                                </x-tables.filters-dropdown>
                            </x-slot:filters>
                        </x-tables.table-header>
                    </x-cards.card-header>
                    <x-cards.card-body class="table-responsive">
                        <x-tables.table :columns=$coupons_columns model="coupon" module="sales" />
                    </x-cards.card-body>
                </x-cards.card>
            </div>
        </div>
    </div>
    <x-sales::coupons.add-coupon-modal :products=$products :categories=$categories :establishments=$establishments />
@endsection

@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script type="text/javascript" src="vfs_fonts.js"></script>
    <script>
        "use strict";
        let couponDataTable;
        let lang = "{{ session('locale') }}"
        const couponTable = $('#kt_coupon_table');
        const couponDataUrl = '{{ route('coupons.index') }}';

        $(document).ready(function() {
            if (!couponTable.length) return;
            initCouponsDatatable();
            handleAddCouponForm();
            selectDeselectAll($('#est-select-all-btn'), $('#est-deselect-all-btn'), '[name="establishments_ids"]');
            selectDeselectAll($('#product-select-all-btn'), $('#product-deselect-all-btn'),
                '[name="products_ids"]');
            selectDeselectAll($('#category-select-all-btn'), $('#category-deselect-all-btn'),
                '[name="categories_ids"]');

            $('select[name="discount_apply_to"], select[name="value_type"], select[name="establishments_ids"], select[name="coupon_deleted_records"], select[name="coupon_status"]')
                .select2({
                    minimumResultsForSearch: -1,
                });

            $('select[name="products_ids"], select[name="categories_ids"]')
                .select2({});

            $("#start_date").flatpickr({
                enableTime: true,
                dateFormat: "Y-m-d H:i",
            });
            $("#end_date").flatpickr({
                enableTime: true,
                dateFormat: "Y-m-d H:i",
            });

            $('#add_coupon_button').on('click', function(e) {
                e.preventDefault();
                $('#add_coupon_modal_form input, select').each(function() {
                    if ($(this).is(':checkbox, :radio')) {
                        $(this).prop('checked', false);
                    } else if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).val(null).trigger('change');
                        $('select[name="categories_ids"]').prop('disabled', true);
                        $('select[name="products_ids"]').prop('disabled', true);
                    } else {
                        $(this).val(null);
                    }
                    $('#product-select-all-btn').prop('disabled', true);
                    $('#category-select-all-btn').prop('disabled', true);
                    $('#product-deselect-all-btn').prop('disabled', true);
                    $('#category-deselect-all-btn').prop('disabled', true);
                });

                $('#add_coupon_modal').modal('toggle');
            });

            $('select[name="discount_apply_to"]').on('change', function() {
                let type = $(this).val();

                const isAll = $(this).val() === 'all';
                const isProduct = $(this).val() === 'product';
                const isCategory = $(this).val() === 'category';

                $('select[name="categories_ids"]').prop('disabled', isAll || isProduct);
                $('select[name="products_ids"]').prop('disabled', isAll || isCategory);

                $('#product-select-all-btn').prop('disabled', isAll || isCategory);
                $('#category-select-all-btn').prop('disabled', isAll || isProduct);
                $('#product-deselect-all-btn').prop('disabled', isAll || isCategory);
                $('#category-deselect-all-btn').prop('disabled', isAll || isProduct);
            });

            $('#apply_to_clients_groups').on('change', function() {
                if ($(this).is(':checked')) {
                    $(this).val(1);
                } else {
                    $(this).val(0);
                }
            });

            $('#is_active').on('change', function() {
                if ($(this).is(':checked')) {
                    $(this).val(1);
                } else {
                    $(this).val(0);
                }
            });

            handleFormFiltersDatatable({
                submitButtonClass: '.coupon_submit_button',
                resetButtonClass: '.coupon_reset_button',
                deleted_filter: "coupon_deleted_records",
                dataUrl: '{{ route('coupons.index') }}',
                dataTable: couponDataTable
            });

            $('#generate_code').on('click', function(e) {
                e.preventDefault();
                ajaxRequest("{{ route('coupons.generate-code') }}", 'GET', {}, false, true).done(function(
                    response) {
                    $('#code').val(response.data);
                });
            });
        });

        function handleFormFiltersDatatable(config) {
            const filters = $(config.submitButtonClass);
            const resetButton = $(config.resetButtonClass);
            const deleted = $(`[data-kt-filter="${config.deleted_filter}"]`);
            const dataUrl = config.dataUrl;
            const dataTable = config.dataTable;

            filters.on('click', function(e) {
                const deletedValue = deleted.val();

                dataTable.ajax.url(dataUrl + '?' + $.param({
                    deleted_records: deletedValue
                })).load();
            });

            resetButton.on('click', function(e) {
                deleted.val(null).trigger('change');
                dataTable.search('').columns().search('').ajax.url(dataUrl).load();
            });
        }

        $(document).on('click', '.coupon-delete-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            let deleteUrl = $(this).data('deleted') ?
                `{{ url('/coupon/force-delete/${id}') }}` :
                `{{ url('/coupon/${id}') }}`;

            showAlert(`{{ __('employee::general.delete_confirm', ['name' => ':name']) }}`.replace(':name',
                    '{{ __('employee::general.this_element') }}'),
                "{{ __('employee::general.delete') }}",
                "{{ __('employee::general.cancel') }}", undefined,
                true, "warning").then(function(t) {
                if (t.isConfirmed) {
                    ajaxRequest(deleteUrl, 'DELETE').done(function() {
                        couponDataTable.ajax.reload();
                    });
                }
            });
        });

        $(document).on('click', '.coupon-restore-btn', function(e) {
            var id = $(this).data('id');
            ajaxRequest(`{{ url('/coupon/restore/${id}') }}`, 'POST').done(function() {
                couponDataTable.ajax.reload();
            });
        })

        $(document).on('click', '.coupon-edit-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');

            let getDetailsUrl =
                `{{ url('/coupon/get-details/${id}') }}`;

            $(this).find('input, select').each(function() {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').remove();
            });

            ajaxRequest(getDetailsUrl, "GET", {}, false, false).done(function(
                response) {
                $('select[name="categories_ids"]').val(response.categories_ids).trigger('change');
                $('select[name="products_ids"]').val(response.products_ids).trigger('change');

                $('select[name="establishments_ids"]').val(response.establishments_ids).trigger('change');
                $('select[name="discount_apply_to"]').val(response.coupon.discount_apply_to).trigger(
                    'change');
                $('select[name="value_type"]').val(response.coupon.value_type).trigger('change');
                $('input[name="coupon_count"]').val(response.coupon.coupon_count);
                $('input[name="person_use_time_count"]').val(response.coupon.person_use_time_count);
                $('input[name="value"]').val(response.coupon.value);
                $('input[name="name"]').val(response.coupon.name);
                $('input[name="code"]').val(response.coupon.code);
                $('input[name="start_date"]').val(response.coupon.start_date);
                $('input[name="end_date"]').val(response.coupon.end_date);
                $('input[name="id"]').val(id);

                if (response.coupon.is_active) {
                    $('input[name="is_active"]').prop('checked', true);
                }
                if (response.coupon.apply_to_clients_groups) {
                    $('input[name="apply_to_clients_groups"]').prop('checked', true);
                }

                $('#add_coupon_modal').modal('toggle');
            });
        });


        function handleAddCouponForm() {
            $('#add_coupon_modal_form').on('submit', function(e) {
                e.preventDefault();

                $(this).find('input, select').each(function() {
                    $(this).removeClass('is-invalid');
                    $(this).siblings('.invalid-feedback').remove();
                });

                if ($('[name="is_active"]').is(':checked')) {
                    $('[name="is_active"]').val(1);
                } else {
                    $('[name="is_active"]').val(0);
                }
                if ($('[name="apply_to_clients_groups"]').is(':checked')) {
                    $('[name="apply_to_clients_groups"]').val(1);
                } else {
                    $('[name="apply_to_clients_groups"]').val(0);
                }

                let establishments_ids = $('select[name="establishments_ids"]').val().map(Number);
                let products_ids = $('select[name="products_ids"]').val().map(Number);
                let categories_ids = $('select[name="categories_ids"]').val().map(Number);

                let data = $(this).serializeArray();

                data = data.filter(item => item.name !== 'establishments');

                data.push({
                    name: "establishments_ids",
                    value: establishments_ids
                }, {
                    name: "products_ids",
                    value: products_ids
                }, {
                    name: "categories_ids",
                    value: categories_ids
                });

                data.push({
                    name: "_token",
                    value: window.csrfToken
                });

                ajaxRequest("{{ route('coupons.store') }}", "POST", data).fail(
                    function(data) {
                        $.each(data.responseJSON.errors, function(key, value) {
                            $(`[name='${key}']`).addClass('is-invalid');
                            $(`[name='${key}']`).after('<div class="invalid-feedback">' + value +
                                '</div>');
                        });
                    }).done(function() {
                    $('#add_coupon_modal').modal('toggle');
                    couponDataTable.ajax.reload();
                });
            })
        }

        function initCouponsDatatable() {
            couponDataTable = $(couponTable).DataTable({
                processing: true,
                serverSide: true,
                ajax: couponDataUrl,
                info: false,
                columns: [{
                        data: 'id',
                        name: 'id',
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'discount_apply_to',
                        name: 'discount_apply_to',
                        class: 'text-center'
                    },
                    {
                        data: 'coupon_count',
                        name: 'coupon_count',
                        class: 'text-center'
                    },
                    {
                        data: 'person_use_time_count',
                        name: 'person_use_time_count',
                        class: 'text-center'
                    },
                    {
                        data: 'value_type',
                        name: 'value_type'
                    },
                    {
                        data: 'value',
                        name: 'value'
                    },
                    {
                        data: 'start_date',
                        name: 'start_date'
                    },
                    {
                        data: 'end_date',
                        name: 'end_date'
                    },
                    {
                        data: 'apply_to_clients_groups',
                        name: 'apply_to_clients_groups'
                    },
                    {
                        data: 'is_active',
                        name: 'is_active'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [],
                scrollX: true,
                pageLength: 10,
                drawCallback: function() {
                    KTMenu.createInstances(); // Reinitialize KTMenu for the action buttons
                }
            });
        };
    </script>
@endsection
