<div class="card-toolbar m-0" style="    justify-items: center;">
    <!--begin::Tab nav-->
    <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold" role="tablist">
        <li class="nav-item" role="presentation">
            <a id="client_contacts_tab" class="nav-link justify-content-center fs-3  text-active-gray-800 active"
                data-bs-toggle="tab" role="tab" href="#client_contacts" aria-selected="true">
                @lang('clientsandsuppliers::fields.1')
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a id="Billing_tab" class="nav-link justify-content-center fs-3 text-active-gray-800"
                data-bs-toggle="tab" role="tab" href="#Billing_tab" aria-selected="false" tabindex="-1">
                @lang('clientsandsuppliers::fields.2')

            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a id="shipping_addresses_tab" class="nav-link justify-content-center fs-3 text-active-gray-800"
                data-bs-toggle="tab" role="tab" href="#shipping_addresses" aria-selected="false" tabindex="-1">
                @lang('clientsandsuppliers::fields.3')
            </a>
        </li>
        {{-- <li class="nav-item" role="presentation">
            <a id="bank_account_information_tab"
                class="nav-link justify-content-center text-active-gray-800 fs-3 text-hover-gray-800" data-bs-toggle="tab"
                role="tab" href="#bank_account_information" aria-selected="false" tabindex="-1">
                @lang('clientsandsuppliers::fields.4')
            </a>
        </li>

        <li class="nav-item" role="presentation">
            <a id="bank_account_information_tab1"
                class="nav-link justify-content-center text-active-gray-800 fs-3 text-hover-gray-800" data-bs-toggle="tab"
                role="tab" href="#bank_account_information1" aria-selected="false" tabindex="-1">
                @lang('clientsandsuppliers::fields.5')
            </a>
        </li> --}}
    </ul>
    <!--end::Tab nav-->
</div>

<div class="tab-content">
    <div id="client_contacts" class="card-body p-0 tab-pane fade show active" role="tabpanel"
        aria-labelledby="client_contacts_tab">
            @include('clientsandsuppliers::Client.empty-data')


    </div>
</div>

<div class="tab-content">
    <div id="Billing" class="card-body p-0 tab-pane fade show" role="tabpanel"
        aria-labelledby="Billing_tab">

        @if (count($contact->transactions) == 0)
        <div class="empty-content">
            <img src="/assets/media/illustrations/empty-content.svg" class=" w-200px" alt="">
            <span class="text-gray-500 fw-semibold fs-6" style="margin: 7px -34px;">
                @lang('messages.no_account_transactions')</span>
        </div>
    @else
        {{-- <div class="card-body py-3">
            <div class="table-responsive">
                <table class="table align-middle gs-0 gy-4">
                    <thead>
                        <tr class="fw-bold  text-muted bg-light">
                            <th class="min-w-125px ">@lang('accounting::lang.transaction_number')</th>
                            <th class="min-w-100px">@lang('accounting::lang.operation_date')</th>
                            <th class="min-w-125px">@lang('accounting::lang.transaction')</th>
                            <th class="min-w-125px">@lang('accounting::lang.cost_center')</th>
                            <th class="min-w-200px">@lang('accounting::lang.added_by')</th>
                            <th class="min-w-150px">@lang('accounting::lang.debit')</th>
                            <th class="min-w-150px">@lang('accounting::lang.credit')</th>
                            <th class="min-w-150px">@lang('accounting::lang.balance')</th>

                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $balance = 0;
                            $total_debit = 0;
                            $total_credit = 0;
                        @endphp
                        @foreach ($contact->transactions as $transactions)
                            @php
                                if ($transactions->type == 'debit') {
                                    $balance += $transactions->amount;
                                    $total_debit += $transactions->amount;
                                } elseif ($transactions->type == 'credit') {
                                    $balance -= $transactions->amount;
                                    $total_credit += $transactions->amount;
                                }
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="d-flex justify-content-start flex-column">
                                            <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6"
                                                @if ($transactions->sub_type == 'sell' || $transactions->sub_type == 'purchases') href="{{ url("/transaction-show/{$transactions->transaction_id}") }}" @endif>
                                                @if ($transactions->sub_type == 'journal_entry')
                                                    {{ $transactions->accTransMapping->ref_no }}
                                                @else
                                                    {{ $transactions->ref_no }}
                                                @endif
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a class="text-gray-900 fw-bold text-hover-primary d-block mb-1 fs-7">
                                        {{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $transactions->operation_date)->format('d/m/Y h:i A') }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-light-primary fs-7">@lang('accounting::lang.' . $transactions->sub_type)</span>
                                </td>
                                <td>
                                    <span class="text-muted fw-semibold text-muted d-block fs-7">
                                        @if ($transactions->costCenter)
                                            {{ $transactions?->costCenter->account_center_number . ' - ' . (App::getLocale() == 'ar' ? $transactions->costCenter->name_ar : $transactions->costCenter->name_en) }}
                                        @else
                                            --
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                        {{ $transactions->createdBy->name }}
                                    </a>
                                </td>
                                <td>
                                    @if ($transactions->type == 'debit')
                                        <a class=" fw-bold text-hover-primary mb-1 fs-6" style="color: #17c653">
                                            {{ number_format($transactions->amount, 2) }}
                                        </a>
                                    @else
                                        <span class="text-muted fw-semibold text-muted d-block fs-4 mt-1">
                                            --
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($transactions->type == 'credit')
                                        <a class=" fw-bold text-hover-primary mb-1 fs-6" style="color: #e90909">
                                            {{ number_format($transactions->amount, 2) }}
                                        </a>
                                    @else
                                        <span class="text-muted fw-semibold text-muted d-block fs-4 mt-1">
                                            --
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <a class=" fw-bold text-hover-primary mb-1 fs-6"
                                        @if ($balance < 0) style="color: #e90909"@else style="color: #17c653" @endif>


                                        @if ($balance < 0)
                                            {{ number_format(abs($balance), 2) }} (-)
                                        @else
                                            {{ number_format($balance, 2) }}
                                        @endif

                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class=" text-center fw-bold fs-4">@lang('accounting::lang.Closing balance')</td>
                            <td colspan="1" class=" fw-bold fs-5" style="color: #17c653">
                                @format_currency($total_debit)
                            </td>
                            <td class=" fw-bold fs-5" style="color: #e90909">
                                @format_currency($total_credit)
                            </td>
                            <td colspan="2" class=" fw-bold fs-5">
                                @format_currency($balance)
                            </td>
                        </tr>
                    </tfoot>


                </table>


            </div>

        </div> --}}
        {{-- {{ $contact->transactions->appends(['account_id' => request('account_id')])->links('pagination::bootstrap-4') }} --}}

    @endif
         </div>
</div>

<div class="tab-content">
    <div id="shipping_addresses" class="card-body p-0 tab-pane fade show" role="tabpanel"
        aria-labelledby="shipping_addresses_tab">
            @include('clientsandsuppliers::Client.empty-data')
          </div>
</div>

<div class="tab-content">
    <div id="bank_account_information" class="card-body p-0 tab-pane fade show" role="tabpanel"
        aria-labelledby="bank_account_information_tab">
            @include('clientsandsuppliers::Client.empty-data')

    </div>
</div>

<div class="tab-content">
    <div id="bank_account_information1" class="card-body p-0 tab-pane fade show" role="tabpanel"
        aria-labelledby="bank_account_information_tab1">
            @include('clientsandsuppliers::Client.empty-data')

    </div>
</div>
