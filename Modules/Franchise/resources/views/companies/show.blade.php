@extends('layouts.app')

@section('title', $company->name_ar)

@section('content')
    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <div class="col-md-4">
            <div class="card card-flush h-md-100">
                <div class="card-header pt-5">
                    <div class="card-title d-flex flex-column">
                        <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ $company->contracts->count() }}</span>
                        <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('franchise::lang.total_contracts') }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-flush h-md-100">
                <div class="card-header pt-5">
                    <div class="card-title d-flex flex-column">
                        <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ $company->contracts->sum('unite_no') }}</span>
                        <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('franchise::lang.total_units') }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-flush h-md-100">
                <div class="card-header pt-5">
                    <div class="card-title d-flex flex-column">
                        <span class="fs-2hx fw-bold text-success me-2 lh-1 ls-n2">{{ number_format($company->contracts->sum('reality_fees'), 2) }} %</span>
                        <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('franchise::lang.total_fees') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-xl-row">
        <div class="flex-column flex-lg-row-auto w-100 w-xl-350px mb-10">
            <div class="card mb-5 mb-xl-8">
                <div class="card-body pt-15">
                    <div class="d-flex flex-center flex-column mb-5">
                        <div class="symbol symbol-100px symbol-circle mb-7">
                            <span class="symbol-label fs-2x fw-bold text-primary bg-light-primary">
                                {{ mb_substr($company->name_ar, 0, 1) }}
                            </span>
                        </div>
                        <a href="#" class="fs-3 text-gray-800 text-hover-primary fw-bold mb-1">
                            {{ app()->getLocale() == 'ar' ? $company->name_ar : $company->name_en }}
                        </a>
                        <span class="badge badge-light-success fw-bold px-4 py-3">{{ $company->account }}</span>
                    </div>

                    <div class="separator separator-dashed my-3"></div>

                    <div id="kt_customer_details_view" class="py-5 fs-6">
                        <div class="fw-bold mt-5">{{ __('franchise::lang.vat_no') }}</div>
                        <div class="text-gray-600"><code>{{ $company->vat_no }}</code></div>

                        <div class="fw-bold mt-5">{{ __('franchise::lang.city_and_address') }}</div>
                        <div class="text-gray-600">
                            <i class="ki-outline ki-geolocation fs-7 me-1"></i>
                            @lang('franchise::lang.cities.' . $company->city) - {{ $company->street }}
                            <br>
                            <small class="text-muted">{{ $company->national_address }}</small>
                        </div>

                        <div class="fw-bold mt-5">{{ __('franchise::lang.contact_info') }}</div>
                        <div class="text-gray-600 d-flex flex-column">
                            <a href="mailto:{{ $company->email }}" class="text-gray-600 text-hover-primary mb-1">
                                <i class="ki-outline ki-sms fs-7 me-1"></i> {{ $company->email }}
                            </a>
                            <a href="tel:{{ $company->mobile }}" class="text-gray-600 text-hover-primary mb-1">
                                <i class="ki-outline ki-phone fs-7 me-1"></i> {{ $company->mobile }}
                            </a>
                            @if ($company->tel)
                                <span class="text-muted">
                                    <i class="ki-outline ki-telephone fs-7 me-1"></i> {{ $company->tel }}
                                </span>
                            @endif
                        </div>

                        <div class="fw-bold mt-5">{{ __('franchise::lang.created_at') }}</div>
                        <div class="text-gray-600">{{ $company->created_at->format('Y-m-d') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-lg-row-fluid ms-lg-15">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <h2 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">{{ __('franchise::lang.contract_history') }}</span>
                    </h2>
                    <div class="card-toolbar">
                        @php
                            $hasActiveContract = $company->contracts->where('status', 'active')->count() > 0;
                        @endphp

                        @if (!$hasActiveContract)
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                data-bs-target="#kt_modal_add_contract">
                                <i class="ki-outline ki-plus fs-2"></i> {{ __('franchise::lang.add_new_contract') }}
                            </button>
                        @else
                            <span class="badge badge-light-warning fw-bold px-4 py-3">
                                <i class="ki-outline ki-information fs-7 me-1 text-warning"></i>
                                {{ __('franchise::lang.active_contract_exists') }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body pt-0">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>{{ __('franchise::lang.contract_duration') }}</th>
                                <th>{{ __('franchise::lang.start_date') }}</th>
                                <th>{{ __('franchise::lang.end_date') }}</th>
                                <th>{{ __('franchise::lang.reality_fees') }}</th>
                                <th>{{ __('franchise::lang.status') }}</th>
                                <th class="text-end min-w-150px">{{ __('franchise::lang.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            @foreach ($company->contracts as $contract)
                                <tr>
                                    <td>{{ $contract->contract_duration }} {{ __('franchise::lang.months') }}</td>
                                    <td>{{ $contract->start_date->format('Y-m-d') }}</td>
                                    <td>{{ $contract->end_date->format('Y-m-d') }}</td>
                                    <td>{{ number_format($contract->reality_fees, 2) }} %</td>
                                    <td>{!! $contract->status_label !!}</td>
                                    <td class="text-end">
                                        @if ($contract->contract_file)
                                            <a href="{{ asset('storage/' . $contract->contract_file) }}" target="_blank"
                                                class="btn btn-icon btn-light-primary btn-sm me-1" title="{{ __('franchise::lang.view_file') }}">
                                                <i class="ki-outline ki-file fs-2"></i>
                                            </a>
                                        @endif
                                        <button type="button" class="btn btn-icon btn-light-warning btn-sm edit-contract-btn me-1"
                                            data-id="{{ $contract->id }}" title="{{ __('franchise::lang.edit') }}">
                                            <i class="ki-outline ki-pencil fs-2"></i>
                                        </button>
                                        <button type="button" class="btn btn-icon btn-light-success btn-sm extend-contract-btn me-1"
                                            data-id="{{ $contract->id }}"
                                            data-end="{{ $contract->end_date->format('Y-m-d') }}"
                                            title="{{ __('franchise::lang.extend_contract') }}">
                                            <i class="ki-outline ki-arrows-loop fs-2"></i>
                                        </button>
                                        <button type="button" class="btn btn-icon btn-light-info btn-sm view-history-btn"
                                            data-id="{{ $contract->id }}" title="{{ __('franchise::lang.view_history') }}">
                                            <i class="ki-outline ki-time fs-2"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kt_modal_extension_history" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">{{ __('franchise::lang.extension_history') }}</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle gs-0 gy-3">
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th>{{ __('franchise::lang.process_date') }}</th>
                                    <th>{{ __('franchise::lang.added_duration') }}</th>
                                    <th>{{ __('franchise::lang.from') }}</th>
                                    <th>{{ __('franchise::lang.to') }}</th>
                                </tr>
                            </thead>
                            <tbody id="extension_history_body"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kt_modal_extend_contract" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-450px">
            <div class="modal-content">
                <form id="kt_modal_extend_contract_form">
                    @csrf
                    <input type="hidden" name="contract_id" id="extend_contract_id">
                    <div class="modal-header">
                        <h2 class="fw-bold">{{ __('franchise::lang.extend_contract') }}</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </div>
                    </div>
                    <div class="modal-body py-10 px-lg-17">
                        <div class="fv-row mb-7">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.extension_duration_months') }}</label>
                            <input type="number" class="form-control form-control-solid" name="extension_duration" min="1" required />
                        </div>
                        <div class="text-muted fs-7">
                            {{ __('franchise::lang.current_end_date') }}: <span id="current_end_date_span" class="fw-bold"></span>
                        </div>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('franchise::lang.cancel') }}</button>
                        <button type="submit" id="kt_modal_extend_contract_submit" class="btn btn-success">
                            <span class="indicator-label">{{ __('franchise::lang.confirm_extension') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kt_modal_add_contract" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <form id="kt_modal_add_contract_form" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="franchise_id" value="{{ $company->id }}">
                    <div class="modal-header">
                        <h2 class="fw-bold">{{ __('franchise::lang.add_new_contract') }}</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </div>
                    </div>
                    <div class="modal-body py-10 px-lg-17">
                        <div class="row g-9 mb-7">
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.start_date') }}</label>
                                <input type="date" class="form-control form-control-solid" name="start_date" required />
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.contract_duration') }}</label>
                                <input type="number" class="form-control form-control-solid" name="contract_duration" required />
                            </div>
                        </div>
                        <div class="row g-9 mb-7">
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.reality_fees') }}</label>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="reality_fees" required />
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.unite_no') }}</label>
                                <input type="number" class="form-control form-control-solid" name="unite_no" value="1" required />
                            </div>
                        </div>
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2">{{ __('franchise::lang.contract_file') }}</label>
                            <input type="file" class="form-control form-control-solid" name="contract_file" accept=".pdf,.jpg,.png" />
                        </div>
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2">{{ __('franchise::lang.notes') }}</label>
                            <textarea class="form-control form-control-solid" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('franchise::lang.cancel') }}</button>
                        <button type="submit" id="kt_modal_add_contract_submit" class="btn btn-primary">
                            <span class="indicator-label">{{ __('franchise::lang.save') }}</span>
                            <span class="indicator-progress">{{ __('franchise::lang.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="kt_modal_edit_contract" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <form id="kt_modal_edit_contract_form" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="contract_id" id="edit_contract_id">
                    <div class="modal-header">
                        <h2 class="fw-bold">{{ __('franchise::lang.edit_contract') }}</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </div>
                    </div>
                    <div class="modal-body py-10 px-lg-17">
                        <div class="row g-9 mb-7">
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.start_date') }}</label>
                                <input type="date" class="form-control form-control-solid" name="start_date" id="edit_start_date" required />
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.contract_duration') }}</label>
                                <input type="number" class="form-control form-control-solid" name="contract_duration" id="edit_contract_duration" required />
                            </div>
                        </div>
                        <div class="row g-9 mb-7">
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.reality_fees') }}</label>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="reality_fees" id="edit_reality_fees" required />
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="required fs-6 fw-semibold mb-2">{{ __('franchise::lang.unite_no') }}</label>
                                <input type="number" class="form-control form-control-solid" name="unite_no" id="edit_unite_no" required />
                            </div>
                        </div>
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2">{{ __('franchise::lang.contract_file') }}</label>
                            <input type="file" class="form-control form-control-solid" name="contract_file" accept=".pdf,.jpg,.png" />
                            <small class="text-muted">{{ __('franchise::lang.keep_current_file') }}</small>
                        </div>
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2">{{ __('franchise::lang.notes') }}</label>
                            <textarea class="form-control form-control-solid" name="notes" id="edit_notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('franchise::lang.cancel') }}</button>
                        <button type="submit" id="kt_modal_edit_contract_submit" class="btn btn-warning">
                            <span class="indicator-label">{{ __('franchise::lang.update') }}</span>
                            <span class="indicator-progress">{{ __('franchise::lang.please_wait') }}
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
        $('#kt_modal_add_contract_form').on('submit', function(e) {
            e.preventDefault();
            const btn = document.querySelector('#kt_modal_add_contract_submit');
            btn.setAttribute('data-kt-indicator', 'on');
            btn.disabled = true;

            let formData = new FormData(this);

            $.ajax({
                url: "{{ route('franchise.contracts.store') }}",
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    location.reload();
                },
                error: function(err) {
                    btn.removeAttribute('data-kt-indicator');
                    btn.disabled = false;
                    Swal.fire("{{ __('franchise::lang.error') }}", "{{ __('franchise::lang.error_occurred') }}", 'error');
                }
            });
        });

        $(document).on('click', '.edit-contract-btn', function() {
            const id = $(this).data('id');
            $.get(`/franchise/contracts/${id}/edit`, function(data) {
                $('#edit_contract_id').val(data.id);
                $('#edit_start_date').val(data.start_date.split('T')[0]);
                $('#edit_contract_duration').val(data.contract_duration);
                $('#edit_reality_fees').val(data.reality_fees);
                $('#edit_unite_no').val(data.unite_no);
                $('#edit_notes').val(data.notes);
                $('#kt_modal_edit_contract').modal('show');
            });
        });

        $('#kt_modal_edit_contract_form').on('submit', function(e) {
            e.preventDefault();
            const id = $('#edit_contract_id').val();
            const btn = document.querySelector('#kt_modal_edit_contract_submit');
            btn.setAttribute('data-kt-indicator', 'on');
            btn.disabled = true;
            let formData = new FormData(this);

            $.ajax({
                url: `/franchise/contracts/${id}`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    location.reload();
                },
                error: function(err) {
                    btn.removeAttribute('data-kt-indicator');
                    btn.disabled = false;
                    Swal.fire("{{ __('franchise::lang.error') }}", "{{ __('franchise::lang.error_occurred') }}", 'error');
                }
            });
        });

        $(document).on('click', '.extend-contract-btn', function() {
            const id = $(this).data('id');
            const endDate = $(this).data('end');
            $('#extend_contract_id').val(id);
            $('#current_end_date_span').text(endDate);
            $('#kt_modal_extend_contract').modal('show');
        });

        $('#kt_modal_extend_contract_form').on('submit', function(e) {
            e.preventDefault();
            const id = $('#extend_contract_id').val();
            const btn = document.querySelector('#kt_modal_extend_contract_submit');
            btn.disabled = true;

            $.ajax({
                url: `/franchise/contracts/${id}/extend`,
                method: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    location.reload();
                },
                error: function(err) {
                    btn.disabled = false;
                    Swal.fire("{{ __('franchise::lang.error') }}", "{{ __('franchise::lang.error_occurred') }}", 'error');
                }
            });
        });

        $(document).on('click', '.view-history-btn', function() {
            const id = $(this).data('id');
            const tbody = $('#extension_history_body');
            tbody.html('<tr><td colspan="4" class="text-center">{{ __('franchise::lang.loading') }}</td></tr>');
            $('#kt_modal_extension_history').modal('show');

            $.get(`/franchise/contracts/${id}/extension-history`, function(data) {
                tbody.empty();
                if (data.length === 0) {
                    tbody.append('<tr><td colspan="4" class="text-center">{{ __('franchise::lang.no_extensions_found') }}</td></tr>');
                    return;
                }
                data.forEach(item => {
                    tbody.append(`
                        <tr>
                            <td>${new Date(item.created_at).toLocaleDateString()}</td>
                            <td><span class="badge badge-light-success">+ ${item.added_months} {{ __('franchise::lang.months') }}</span></td>
                            <td>${item.old_end_date}</td>
                            <td><span class="text-primary fw-bold">${item.new_end_date}</span></td>
                        </tr>
                    `);
                });
            });
        });
    </script>
@endsection
