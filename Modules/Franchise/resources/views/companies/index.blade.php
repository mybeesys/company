@extends('layouts.app')
@section('title', __('franchise::lang.franchise'))

@section('content')
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
                <button type="button" class="btn btn-primary" onclick="addCompanyModal()">
                    <i class="ki-outline ki-plus fs-2"></i> {{ __('franchise::lang.add_new') }}
                </button>
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

    <div class="modal fade" id="kt_modal_add_company" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-850px">
            <div class="modal-content">
                <form id="kt_modal_add_company_form">
                    @csrf
                    <input type="hidden" name="company_id" id="company_id">
                    <div class="modal-header">
                        <h2 class="fw-bold" id="modal_title">{{ __('franchise::lang.save') }}</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </div>
                    </div>
                    <div class="modal-body py-10 px-lg-17">
                        <div class="row g-9 mb-7">
                            <div class="col-md-6 fv-row">
                                <label
                                    class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.franchisee_name_ar') }}</label>
                                <input type="text" class="form-control form-control-solid" name="name_ar" id="name_ar"
                                    required />
                            </div>
                            <div class="col-md-6 fv-row">
                                <label
                                    class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.franchisee_name_en') }}</label>
                                <input type="text" class="form-control form-control-solid" name="name_en" id="name_en"
                                    required />
                            </div>
                        </div>
                        <div class="row g-9 mb-7">
                            <div class="col-md-4 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.city') }}</label>
                                <select name="city" id="city" class="form-select form-select-solid"
                                    data-control="select2" data-dropdown-parent="#kt_modal_add_company"
                                    data-placeholder="{{ __('franchise::lang.city') }}" data-allow-clear="true">
                                    <option></option>
                                    @foreach (['riyadh', 'jeddah', 'dammam', 'khobar', 'dhahran', 'medina', 'mecca', 'taif', 'tabuk', 'hail', 'qassim', 'buraidah', 'unaizah', 'abha', 'khamis_mushait', 'jizan', 'najran', 'al_jouf', 'arar', 'jubail', 'yanbu'] as $city_key)
                                        <option value="{{ $city_key }}">
                                            {{ __('franchise::lang.cities.' . $city_key) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 fv-row">
                                <label class="fs-6 fw-semibold mb-2">{{ __('franchise::lang.street') }}</label>
                                <input type="text" class="form-control form-control-solid" name="street"
                                    id="street" />
                            </div>
                            <div class="col-md-4 fv-row">
                                <label class="fs-6 fw-semibold mb-2">{{ __('franchise::lang.national_address') }}</label>
                                <input type="text" class="form-control form-control-solid" name="national_address"
                                    id="national_address" />
                            </div>
                        </div>
                        <div class="row g-9 mb-7">
                            <div class="col-md-4 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.vat_no') }}</label>
                                <input type="text" class="form-control form-control-solid" name="vat_no" id="vat_no"
                                    required />
                            </div>
                            <div class="col-md-4 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.mobile') }}</label>
                                <input type="text" class="form-control form-control-solid" name="mobile" id="mobile"
                                    required />
                            </div>
                            <div class="col-md-4 fv-row">
                                <label class="fs-6 fw-semibold mb-2">{{ __('franchise::lang.tel') }}</label>
                                <input type="text" class="form-control form-control-solid" name="tel"
                                    id="tel" />
                            </div>
                        </div>
                        <div class="row g-9 mb-7">
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.email') }}</label>
                                <input type="email" class="form-control form-control-solid" name="email"
                                    id="email" required />
                            </div>
                            <div class="col-md-6 fv-row">
                                <label
                                    class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.accounting_account') }}</label>
                                <select name="account" id="account" class="form-select form-select-solid"
                                    data-control="select2" data-dropdown-parent="#kt_modal_add_company">
                                    @foreach (\Modules\Accounting\Models\AccountingAccount::where('status', 'active')->get() as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->name_ar }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light"
                            data-bs-dismiss="modal">{{ __('franchise::lang.cancel') }}</button>
                        <button type="submit" id="kt_modal_add_company_submit" class="btn btn-primary">
                            <span class="indicator-label">{{ __('franchise::lang.save') }}</span>
                            <span class="indicator-progress">{{ __('franchise::lang.please_wait') }}...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        let currentView = 'all';
        let table = $('#companies_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('franchise.companies.index') }}",
                data: function(d) {
                    d.view_type = currentView;
                }
            },
            columns: [{
                    data: 'name_ar'
                },
                {
                    data: 'city'
                },
                {
                    data: 'vat_no'
                },
                {
                    data: 'status_label'
                },
                {
                    data: 'mobile'
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            drawCallback: function() {
                KTMenu.createInstances();
            }
        });

        $('.filter-tab').on('click', function(e) {
            e.preventDefault();
            $('.filter-tab').removeClass('active');
            $(this).addClass('active');
            currentView = $(this).data('view');
            table.draw();
        });

        function addCompanyModal() {
            $('#kt_modal_add_company_form')[0].reset();
            $('#company_id').val('');
            $('#city, #account').val(null).trigger('change');
            $('#modal_title').text("{{ __('franchise::lang.add_new') }}");
            $('#kt_modal_add_company').modal('show');
        }

        function editCompany(id) {
            $.get(`/franchise/companies/${id}`, function(data) {
                $('#company_id').val(data.id);
                $('#name_ar').val(data.name_ar);
                $('#name_en').val(data.name_en);
                $('#vat_no').val(data.vat_no);
                $('#mobile').val(data.mobile);
                $('#email').val(data.email);
                $('#street').val(data.street);
                $('#tel').val(data.tel);
                $('#national_address').val(data.national_address);
                $('#city').val(data.city).trigger('change');
                $('#account').val(data.account).trigger('change');
                $('#modal_title').text("{{ __('franchise::lang.edit') }}");
                $('#kt_modal_add_company').modal('show');
            });
        }

        $('#kt_modal_add_company_form').on('submit', function(e) {
            e.preventDefault();
            const btn = document.querySelector('#kt_modal_add_company_submit');
            btn.setAttribute('data-kt-indicator', 'on');
            btn.disabled = true;

            let id = $('#company_id').val();
            let url = id ? `/franchise/companies/${id}` : "{{ route('franchise.companies.store') }}";
            let method = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: 'POST',
                data: $(this).serialize() + (id ? '&_method=PUT' : ''),
                success: function(res) {
                    btn.removeAttribute('data-kt-indicator');
                    btn.disabled = false;
                    $('#kt_modal_add_company').modal('hide');
                    table.draw(false);
                    Swal.fire({
                        text: res.message,
                        icon: "success",
                        confirmButtonText: "{{ __('franchise::lang.ok') }}",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                },
                error: function(err) {
                    btn.removeAttribute('data-kt-indicator');
                    btn.disabled = false;
                    let errorMessage = "{{ __('franchise::lang.error') }}";

                    if (err.responseJSON && err.responseJSON.message) {
                        errorMessage = err.responseJSON.message;
                    }

                    Swal.fire({
                        text: errorMessage,
                        icon: "error",
                        confirmButtonText: "{{ __('franchise::lang.ok') }}",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                }
            });
        });
    </script>
@endsection
