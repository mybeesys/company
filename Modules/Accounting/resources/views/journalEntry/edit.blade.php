@extends('layouts.app')


@php
    if ($duplication) {
        $title = __('accounting::fields.duplication') . '-' . $acc_trans_mapping->ref_no;
    } else {
        $title = __('accounting::lang.edit_journalEntry');
    }

@endphp
@section('title', $title)
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }
    </style>


@stop
@section('content')

    <div class="container">
        <div class="row">
            <div class="col-6">
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    @if ($duplication)
                        <h1> @lang('accounting::fields.duplication') - ( {{ $acc_trans_mapping->ref_no }} )</h1>
                    @else
                        <h1> @lang('accounting::lang.edit_journalEntry')</h1>
                    @endif

                </div>
            </div>
            <div class="col-6" style="justify-content: end;display: flex;">
                <div class="row">

                    {{-- <div class="navigation-buttons"> --}}
                    <div class="col-2">
                        @if ($previous)
                            @if ($duplication)
                                <a href="{{ route('journal-entry-duplication', $previous->id) }}" class="btn btn-primary"
                                    style="padding: 5px;
                               border-radius: 50%;"><i
                                        @if (app()->getLocale() == 'en') class="ki-outline ki-arrow-left fs-1 p-0" @endif
                                        @if (app()->getLocale() == 'ar') class="ki-outline ki-arrow-right fs-1 p-0" @endif></i></a>
                            @else
                                <a href="{{ route('journal-entry-edit', $previous->id) }}" class="btn btn-primary "
                                    style="padding: 5px;
                                border-radius: 50%;"><i
                                        @if (app()->getLocale() == 'en') class="ki-outline ki-arrow-left fs-1 p-0" @endif
                                        @if (app()->getLocale() == 'ar') class="ki-outline ki-arrow-right fs-1 p-0" @endif></i></a>
                            @endif
                        @endif
                    </div>
                    <div class="col-8">

                        <select id="acc_trans_mappings" class="form-select form-select-solid select-2" name="id">

                            @foreach ($acc_trans_mappings as $acc_trans)
                                <option value="{{ $acc_trans->id }}" @if ($acc_trans_mapping->id == $acc_trans->id) selected @endif>

                                    {{ $acc_trans->ref_no }}
                                </option>
                            @endforeach

                        </select>
                    </div>
                    <div class="col-2">

                        @if ($next)
                            @if ($duplication)
                                <a href="{{ route('journal-entry-duplication', $next->id) }}" class="btn btn-primary"
                                    style="padding: 5px;
                                border-radius: 50%;"><i
                                        @if (app()->getLocale() == 'en') class="ki-outline ki-arrow-right fs-1 p-0" @endif
                                        @if (app()->getLocale() == 'ar') class="ki-outline ki-arrow-left fs-1 p-0" @endif></i></a>
                            @else
                                <a href="{{ route('journal-entry-edit', $next->id) }}" class="btn btn-primary"
                                    style="padding: 5px;
                                border-radius: 50%;"><i
                                        @if (app()->getLocale() == 'en') class="ki-outline ki-arrow-right fs-1 p-0" @endif
                                        @if (app()->getLocale() == 'ar') class="ki-outline ki-arrow-left fs-1 p-0" @endif></i></a>
                            @endif
                        @endif
                        {{-- </div> --}}
                    </div>
                </div>
            </div>

            <div class="col-12 text-start mt-2">
                @if (!empty($acc_trans_mapping->path_file))
                    <a href="{{ route('journal-entry-attachment', $acc_trans_mapping->id) }}" class="btn btn-light-primary">
                        @lang('accounting::lang.view_attachment')
                    </a>
                @endif
            </div>
        </div>
    </div>
    <div class="container mt-2">
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ url("/journal-entry-export-pdf/{$acc_trans_mapping->id}") }}" class="btn btn-export-pdf"
                style="width: 10rem;padding: 5px;">@lang('general.export_as_pdf')</a>
            <a href="{{ url("/journal-entry-export-excel/{$acc_trans_mapping->id}") }}" class="btn btn-export-excel"
                style="width: 10rem;padding: 5px;">@lang('general.export_as_excel')</a>
        </div>
    </div>

    <div class="separator d-flex flex-center my-5">

        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
    </div>
    @if ($duplication)
        <form id="journalEntryForm" method="POST" action="{{ route('journal-entry-store') }}" enctype="multipart/form-data">
        @else
            <form id="journalEntryForm" method="POST"
                action="{{ route('journal-entry-update', $acc_trans_mapping->id) }}" enctype="multipart/form-data">
    @endif
    @csrf

    <div class="row g-10" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
        <div class="col-lg-6">
            <div class="fv-row mb-9 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid">

                <label class="fs-6 fw-semibold mb-2 required">@lang('accounting::lang.journalEntry_date')</label>

                <input class="form-control form-control-solid required flatpickr-input" name="journalEntry_date" required
                    value="{{ \Carbon\Carbon::parse($acc_trans_mapping->operation_date)->format('Y-m-d') }}" placeholder="@lang('accounting::lang.Pick_journalEntry_date')"
                    id="kt_calendar_datepicker_start_date" type="text" data-gtm-form-interact-field-id="1">

                <div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback"></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="d-flex flex-column mb-8" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                <label class="fs-6 fw-semibold mb-2">@lang('purchases::lang.description')</label>

                <textarea class="form-control form-control-solid" rows="1" name="additionalNotes">{{ $acc_trans_mapping->note }}</textarea>
            </div>
        </div>


    </div>

    <div class="card mb-5 mb-xl-8" @if (app()->getLocale() == 'ar') dir="rtl" @endif>

        <div class="card-header border-0 pt-5">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold fs-3 mb-1">@lang('accounting::lang.Journal Entry Party')</span>

            </h3>
            <div class="card-toolbar">
                <div class="btn-group dropend">

                    <button type="button" style="background: transparent;adding: 2px 7px 8px 13px;border-radius: 6px;"
                        class="btn  dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog" style="font-size: 1.4rem; color: #c59a00;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu" style=" width: max-content;padding: 10px;"
                        style="padding: 8px 15px;">
                        <li class="mb-5" style="text-align: justify;">
                            <span class="card-label fw-bold fs-6 mb-1">@lang('messages.settings')</span>
                        </li>
                        <li>
                            <div class="form-check form-switch my-3"
                                style="    display: flex; justify-content: space-between; gap: 37px;">
                                <input class="form-check-input" type="checkbox" id="toggleCostCenter">
                                <label class="form-check-label ml-4" for="toggleCostCenter">@lang('accounting::lang.Enable Cost Center')</label>
                            </div>
                        </li>




                    </ul>
                </div>
            </div>
        </div>



        <div class="card-body py-3">
            <div class="table-responsive">

                <table class="table align-middle gs-0 gy-4">
                    <thead>
                        <tr class="fw-bold  text-muted bg-light">
                            <th class="min-w-125px ">@lang('accounting::lang.account')</th>
                            <th class="min-w-80px cost-center-column" style="display:none">@lang('accounting::lang.cost_center')</th>
                            <th class="min-w-125px">@lang('accounting::lang.debit')</th>
                            <th class="min-w-125px">@lang('accounting::lang.credit')</th>
                            <th class="min-w-200px">@lang('accounting::lang.ledger_narration')</th>
                            <th class="min-w-25px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($acc_trans_mapping->transactions as $transaction)
                            <tr>
                                <td>
                                    <select required
                                        class="form-select select-2  form-select-solid kt_ecommerce_select2_account "
                                        name="account_id">

                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}"
                                                @if ($transaction->accounting_account_id == $account->id) selected @endif>
                                                @if (app()->getLocale() == 'ar')
                                                    {{ $account->name_ar }} - <span
                                                        class="fw-semibold mx-2 text-muted fs-5">@lang('accounting::lang.' . $account->account_primary_type)</span>
                                                @else
                                                    {{ $account->name_en }} - <span
                                                        class="fw-semibold mx-2 text-muted fs-7">@lang('accounting::lang.' . $account->account_primary_type)</span>
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="cost-center-column" style="display:none">

                                    <select
                                        class="form-select select-2 form-select-solid  kt_ecommerce_select2_cost_center"
                                        name="cost_center">

                                        @foreach ($cost_centers as $cost_center)
                                            <option value="{{ $cost_center->id }}"
                                                @if ($transaction->cost_center_id == $cost_center->id) selected @endif>
                                                @if (app()->getLocale() == 'ar')
                                                    {{ $cost_center->name_ar }} - <span
                                                        class="fw-semibold mx-2 text-muted fs-7">
                                                        {{ $cost_center->account_center_number }}</span>
                                                @else
                                                    {{ $cost_center->name_en }} - <span
                                                        class="fw-semibold mx-2 text-muted fs-7">{{ $cost_center->account_center_number }}</span>
                                                @endif
                                            </option>
                                        @endforeach


                                    </select>
                                </td>
                                <td>
                                    @if ($transaction->type == 'debit')
                                        <input type="number"
                                            class="form-control form-control-lg form-control debit-field" name="debit"
                                            id="debit" step="any" placeholder="0.0"
                                            value="{{ $transaction->amount }}" style="width: 100px;"
                                            data-gtm-form-interact-field-id="4">
                                    @else
                                        <input type="number"
                                            class="form-control form-control-lg form-control debit-field" name="debit"
                                            id="debit" step="any" placeholder="0.0" value=""
                                            style="width: 100px;" data-gtm-form-interact-field-id="4">
                                    @endif

                                </td>

                                <td>
                                    @if ($transaction->type == 'credit')
                                        <input type="number"
                                            class="form-control form-control-lg form-control credit-field" name="credit"
                                            id="credit" placeholder="0.0" value="{{ $transaction->amount }}"
                                            style="width: 107px;" step="any" data-gtm-form-interact-field-id="2">
                                    @else
                                        <input type="number"
                                            class="form-control form-control-lg form-control credit-field" name="credit"
                                            id="credit" placeholder="0.0" step="any" value=""
                                            style="width: 107px;" data-gtm-form-interact-field-id="2">
                                    @endif

                                </td>

                                <td>
                                    <textarea class="form-control form-control-solid" rows="1" id="notes" name="notes">{{ $transaction->note }}</textarea>

                                </td>
                                <td>
                                    <button class="btn btn-icon btn-danger delete-row" type="button">
                                        <i class="ki-outline ki-trash fs-2"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="1" class="total"> <a class="btn btn-xs btn-default text-primary"
                                    id="addJournalEntry">
                                    <i class="ki-outline ki-plus fs-2"></i>
                                    @lang('accounting::lang.new_row')
                                </a>
                                @lang('messages.total')
                            </td>


                            <td id="totalDebit">0.00</td>
                            <td id="totalCredit">0.00</td>
                            {{-- <td colspan="1"> </td> --}}
                            <td colspan="2" id="Budget" style="text-align: center;color:red"> </td>
                        </tr>
                    </tfoot>

                </table>

            </div>

        </div>



        {{-- <div> @include('accounting::journalEntry.addingJournalEntryParty', [
            'accounts' => $accounts,
            'cost_centers' => $cost_centers,
        ]) --}}
    </div>

    <div class="fv-row mb-4 col-12" @if (app()->getLocale() == 'ar') dir="rtl" @endif>

        <div class="dropzone dz-clickable" style="padding: 8px 1.75rem;" id="kt_modal_upload_attachments">

            <div class="dz-message needsclick">

                <i class="ki-outline ki-file-up fs-2hx text-primary mx-2"></i>


                <div class="ms-4 " style="text-align: justify">
                    <h3 class="dfs-5 fw-bold text-gray-900 mb-1 fs-6">@lang('accounting::lang.upload_attachment')</h3>
                    <span id="uploadInstructions" class="fw-semibold fs-6 text-muted">@lang('accounting::lang.upload_file')</span>
                </div>

            </div>
        </div>

        <input type="file" id="fileInput" name="attachment" style="display: none;">

    </div>
    </div>

    <input type="hidden" id="JournalEntries" name="JournalEntries" value="">

    <input type="hidden" id="submit_type" name="submit_type" value="save">

    <div class="my-7 d-flex justify-content-end">
        <div class="btn-group">
            <button type="submit" id="primarySubmitBtn" class="btn btn-primary">
                @lang('messages.save')
            </button>
            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                data-bs-toggle="dropdown" aria-expanded="false"></button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item journal-submit-option" href="#" data-submit="save">@lang('messages.save')</a></li>
                <li><a class="dropdown-item journal-submit-option" href="#" data-submit="print">@lang('messages.save&print')</a></li>
                @if ($acc_trans_mapping->is_manual)
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ url("/journal-entry-duplication/{$acc_trans_mapping->id}") }}">
                            @lang('accounting::fields.duplication')
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
    </form>
    @include('accounting::journalEntry.create-account', [
        'parents_account' => $parents_account,
        'account_main_types' => $account_main_types,
        'account_category' => $account_category,
    ])
@stop

@section('script')

    <script>
        flatpickr("#kt_calendar_datepicker_start_date", {
            enableTime: false,
            dateFormat: "Y-m-d"
        });

        $(document).on('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });

        $('#toggleCostCenter').on('change', function() {
            if ($(this).is(':checked')) {
                $('.cost-center-column').show();
                $('.total').attr('colspan', 2);
            } else {
                $('.cost-center-column').hide();
                $('.total').attr('colspan', 1);
            }
        });




        $('#acc_trans_mappings').select2();
        const newRow = `
        <tr>
            <td>

                    <select  required
                                    class="form-select form-select-solid select-2 kt_ecommerce_select2_account" name="account_id">

                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}">
                                            @if (app()->getLocale() == 'ar')
                                                {{ $account->name_ar }} - <span
                                                    class="fw-semibold mx-2 text-muted fs-5">@lang('accounting::lang.' . $account->account_primary_type)</span>
                                            @else
                                                {{ $account->name_en }} - <span
                                                    class="fw-semibold mx-2 text-muted fs-7">@lang('accounting::lang.' . $account->account_primary_type)</span>
                                            @endif
                                        </option>
                                    @endforeach

                </select>
            </td>
            <td class="cost-center-column" style="display:none">

                    <select
                                    class="form-select form-select-solid select-2 kt_ecommerce_select2_cost_center" name="cost_center">
                        <option selected value="">@lang('messages.select')</option>

                                    @foreach ($cost_centers as $cost_center)
                                        <option value="{{ $cost_center->id }}">
                                            @if (app()->getLocale() == 'ar')
                                                {{ $cost_center->name_ar }} - <span
                                                    class="fw-semibold mx-2 text-muted fs-7">
                                                    {{ $cost_center->account_center_number }}</span>
                                            @else
                                                {{ $cost_center->name_en }} - <span
                                                    class="fw-semibold mx-2 text-muted fs-7">{{ $cost_center->account_center_number }}</span>
                                            @endif
                                        </option>
                                    @endforeach



                </select>
            </td>
            <td><input type="number" class="form-control debit-field" step="0.01" min="0" name="debit" placeholder="0.00" style="width: 100px;"></td>
            <td><input type="number" class="form-control credit-field" step="0.01" min="0" name="credit" placeholder="0.00" style="width: 107px;"></td>
            <td><textarea class="form-control form-control-solid" rows="1" name="notes"></textarea></td>
            <td>
                <button class="btn btn-icon btn-danger delete-row" type="button">
                    <i class="ki-outline ki-trash fs-2"></i>
                </button>
            </td>
        </tr>
    `;
      $('#addJournalEntry').on('click', function() {
    var $row1 = $(newRow);
    var $row2 = $(newRow);

    $('table tbody').append($row1);
    $('table tbody').append($row2);

    $row1.find('.kt_ecommerce_select2_cost_center').select2();
    $row2.find('.kt_ecommerce_select2_cost_center').select2();

    var select2Config = {
        ajax: {
            url: '{{ route("accounts-dropdown") }}',
            dataType: 'json',
            processResults: function(data) {
                return { results: data };
            },
        },
        language: {
            noResults: function() {
                return `<a class="link-underline" data-bs-toggle="modal" data-bs-target="#kt_modal_create_account">@lang('accounting::lang.add_account')</a>`;
            }
        },
        escapeMarkup: function(markup) { return markup; },
        templateResult: function(data) { return data.html || data.text; },
        templateSelection: function(data) { return data.text; }
    };

    $row1.find('.kt_ecommerce_select2_account').select2(select2Config);
    $row2.find('.kt_ecommerce_select2_account').select2(select2Config);

    updateTotals();
    if ($('#toggleCostCenter').is(':checked')) {
        $('.cost-center-column').show();
    } else {
        $('.cost-center-column').hide();
    }
});
        $(document).on('shown.bs.modal', '#kt_modal_create_account', function() {
            $(this).find('#kt_ecommerce_select2_account_type').select2({
                dropdownParent: $('#kt_modal_create_account')
            });

        });

        $(document).ready(function() {


            $('.kt_ecommerce_select2_cost_center').select2();
            $('#kt_ecommerce_select2_account_type').select2();
            $('.kt_ecommerce_select2_account').select2({
                ajax: {
                    url: '{{ route('accounts-dropdown') }}',
                    dataType: 'json',
                    processResults: function(data) {
                        return {
                            results: data
                        }
                    },
                },

                language: {
                    noResults: function() {
                        var newAccountText = "@lang('accounting::lang.add_account')";
                        var $newAccountButton = $(
                            '<a class="link-underline" data-bs-toggle="modal" data-bs-target="#kt_modal_create_account" id="addNewAccountBtn">' +
                            newAccountText + '</a>'
                        );
                        $newAccountButton.on('click', function() {
                            $('.kt_ecommerce_select2_account').select2('close');
                        });
                        return $newAccountButton;
                    }
                },

                escapeMarkup: function(markup) {
                    return markup;
                },

                templateResult: function(data) {
                    return data.html || data.text;
                },
                templateSelection: function(data) {
                    return data.text;
                }
            });

            $('#addAccountForm').on('submit', function(e) {
                e.preventDefault();

                $('#submitBtn .indicator-label').hide();
                $('#submitBtn .indicator-progress').show();

                $.ajax({
                    url: "{{ route('store-account') }}",
                    method: "POST",
                    data: $(this).serialize(),
                    success: function(response) {
                        $('#kt_modal_create_account').modal('hide');
                        $('#addAccountForm')[0].reset();
                    },
                    error: function(xhr) {
                    },
                    complete: function() {
                        $('#submitBtn .indicator-label').show();
                        $('#submitBtn .indicator-progress').hide();
                    }
                });
            });


            $('#acc_trans_mappings').on('change', function() {
                var selectedValue = this.value;
                var duplication = {{ $duplication }};
                let url = '';

                if (duplication) {
                    url = '{{ url('journal-entry-duplication') }}/' + selectedValue;
                } else {
                    url = '{{ url('journal-entry-edit') }}/' + selectedValue;
                }
                console.log(url, duplication, selectedValue);

                window.location.href = url;
            });

            // $('#kt_ecommerce_select2_account').select2();
            $('#kt_ecommerce_select2_cost_center').select2();
            updateTotals();
            if ($('#toggleCostCenter').is(':checked')) {
                $('.cost-center-column').show();
            } else {
                $('.cost-center-column').hide();
            }
            updateTotals();
        });

        let totalDebit = 0;
        let totalCredit = 0;

        function roundMoney(value) {
            const n = parseFloat(value);
            if (!Number.isFinite(n)) {
                return 0;
            }

            return Math.round((n + Number.EPSILON) * 100) / 100;
        }

        function formatMoney(value) {
            return roundMoney(value).toFixed(2);
        }

       function updateTotals() {
    let rawDebit = 0;
    let rawCredit = 0;

    $('table tbody tr').each(function() {
        rawDebit += parseFloat($(this).find('.debit-field').val()) || 0;
        rawCredit += parseFloat($(this).find('.credit-field').val()) || 0;
    });

    totalDebit = roundMoney(rawDebit);
    totalCredit = roundMoney(rawCredit);

    const diff = Math.abs(totalDebit - totalCredit);

    if (diff > 0) {
        let budgetDifferenceText = "@lang('accounting::lang.The journal entry is unbalanced with a difference of')";
        $('#Budget').text(budgetDifferenceText + ' : ( ' + diff.toFixed(2) + ' ) ');
    } else {
        $('#Budget').text('');
    }

    $('#totalDebit').text(totalDebit.toFixed(2));
    $('#totalCredit').text(totalCredit.toFixed(2));
}

        $(document).on('click', '.delete-row', function() {

            const rowIndex = $(this).closest('tr').index();


            $(this).closest('tr').remove();
            updateTotals();
            $('.stepper-item').eq(rowIndex).remove();


            stepCounter--;
            $('.stepper-item').each(function(index) {
                $(this).find('.stepper-number').text(index + 1);
            });


            if ($('table tbody tr').length === 0) {
                $('#kt_modal_create_app_stepper').hide();
                stepCounter = 1;
            }

            journalEntries.splice(rowIndex, 1);

            console.log(journalEntries);
        });




        $(document).on('input', '.debit-field', function() {
            const row = $(this).closest('tr');
            const creditField = row.find('.credit-field');

            if ($(this).val() !== '') {
                creditField.val('');
                updateTotals();
            }
        });


        $(document).on('input', '.credit-field', function() {
            const row = $(this).closest('tr');
            const debitField = row.find('.debit-field');

            if ($(this).val() !== '') {
                debitField.val('');
                updateTotals();
            }
        });

        // });
        document.getElementById('kt_modal_upload_attachments').addEventListener('click', function() {
            document.getElementById('fileInput').click();
        });


        document.getElementById('fileInput').addEventListener('change', function(event) {
            const files = event.target.files;
            const uploadInstructions = document.getElementById('uploadInstructions');


            if (files.length > 0) {
                let fileNames = [];
                for (let i = 0; i < files.length; i++) {
                    fileNames.push(files[i].name);
                }

                uploadInstructions.textContent = fileNames.join(', ');
            } else {

                uploadInstructions.textContent = 'Upload file';
            }
        });


        function getJournalEntries() {
            let journalEntries = [];

            $('table tbody tr').each(function() {
                let account_id = $(this).find('select[name="account_id"]').val();
                let cost_center = $(this).find('select[name="cost_center"]').val();
                let debit = $(this).find('.debit-field').val();
                let credit = $(this).find('.credit-field').val();
                let notes = $(this).find('textarea[name="notes"]').val();

                journalEntries.push({
                    account_id: account_id,
                    cost_center: cost_center,
                    debit: debit,
                    credit: credit,
                    notes: notes
                });
            });

            return journalEntries;
        }

        const submitLabelByType = {
            save: @json(__('messages.save')),
            print: @json(__('messages.save&print')),
        };

        $(document).on('click', '.journal-submit-option', function(e) {
            e.preventDefault();
            const t = $(this).data('submit');
            if (!t) return;
            $('#submit_type').val(t);
            $('#primarySubmitBtn').text(submitLabelByType[t] || submitLabelByType.save);
        });

        $('#journalEntryForm').on('submit', function(event) {
            event.preventDefault();
            const diff = Math.abs(totalDebit - totalCredit);
            if (diff > 0 || (totalDebit == 0 && totalCredit == 0)) {
                Swal.fire({
                    title: '@lang('accounting::lang.Error in the process')',
                    text: "@lang('accounting::lang.The journal entry is unbalanced with a difference between debit and credit.')",
                    icon: 'warning',
                });
            } else {
                let journalEntriesData = JSON.stringify(getJournalEntries());
                $('#JournalEntries').val(journalEntriesData);
                this.submit();
            }

        });
    </script>
@stop
