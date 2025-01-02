<div class="card-toolbar m-0" style="    justify-items: center;">
    <!--begin::Tab nav-->
    <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold" role="tablist">
        <li class="nav-item" role="presentation">
            <a id="account_tab" class="nav-link justify-content-center fs-3  text-active-gray-800 active"
                data-bs-toggle="tab" role="tab" href="#account" aria-selected="true">
                @lang('clientsandsuppliers::fields.1')
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a id="Billing_tab" class="nav-link justify-content-center fs-3 text-active-gray-800" data-bs-toggle="tab"
                role="tab" href="#Billing" aria-selected="false" tabindex="-1">
                @lang('clientsandsuppliers::fields.2')

            </a>
        </li>
        {{-- <li class="nav-item" role="presentation">
            <a id="shipping_addresses_tab" class="nav-link justify-content-center fs-3 text-active-gray-800"
                data-bs-toggle="tab" role="tab" href="#shipping_addresses" aria-selected="false" tabindex="-1">
                @lang('clientsandsuppliers::fields.3')
            </a>
        </li> --}}
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
    <div id="account" class="card-body p-0 tab-pane fade show active" role="tabpanel"
        aria-labelledby="account_tab">
        @include('clientsandsuppliers::Client.empty-data')
    </div>
</div>

<div class="tab-content">
    <div id="Billing" class="card-body p-0 tab-pane fade show" role="tabpanel" aria-labelledby="Billing_tab">

        @if ($contact->sales->isEmpty())
        @include('clientsandsuppliers::Client.empty-data')

        @else
            <div class="card-body py-3">
                <div class="table-responsive">
                    <table class="table align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold  text-muted bg-light">
                                <th class="min-w-125px ">@lang('sales::fields.ref_no')</th>
                                <th class="min-w-125px">@lang('accounting::lang.transaction')</th>
                                <th class="min-w-125px">@lang('sales::fields.transaction_date')</th>
                                <th class="min-w-125px">@lang('sales::fields.due_date')</th>
                                <th class="min-w-100px">@lang('sales::fields.payment_status')</th>
                                <th class="min-w-150px">@lang('sales::fields.total_before_vat')</th>
                                <th class="min-w-100px">@lang('sales::fields.vat_value')</th>
                                <th class="min-w-100px">@lang('sales::fields.discount')</th>
                                <th class="min-w-150px">@lang('sales::fields.amount')</th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($contact->sales as $transactions)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex justify-content-start flex-column">
                                                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6"
                                                    href="{{ url("/transaction-show/{$transactions->id}") }}">
                                                    {{ $transactions->ref_no }}

                                                </a>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge badge-light-primary fs-7">@lang('accounting::lang.' . $transactions->type)</span>
                                    </td>
                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary d-block mb-1 fs-7">
                                            {{ $transactions->transaction_date }}
                                        </a>
                                    </td>

                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary d-block mb-1 fs-7">
                                            {{ $transactions->due_date }}
                                        </a>
                                    </td>



                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            @if ($transactions->payment_status == 'paid')
                                                <span class="badge badge-light-info px-3 py-3 fs-base">
                                                    @lang('general::lang.paid') </span>
                                            @elseif ($transactions->payment_status == 'due')
                                                <span class="badge badge-light-danger px-3 py-3 fs-base">

                                                    @lang('general::lang.due') </span>
                                            @elseif ($transactions->payment_status == 'partial')
                                                <span class="badge badge-light-success px-3 py-3 fs-base">

                                                    @lang('general::lang.partial') </span>
                                            @endif
                                        </a>
                                    </td>

                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            {{ $transactions->total_before_tax }}
                                        </a>
                                    </td>
                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            {{ $transactions->tax_amount }}
                                        </a>
                                    </td>

                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            {{ $transactions->discount_amount ?? '0.00' }}
                                        </a>
                                    </td>
                                    <td>
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            {{ $transactions->final_total }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        {{-- <tfoot>
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
                    </tfoot> --}}


                    </table>


                </div>

            </div>
            {{-- {{ $contact->transactions->appends(['account_id' => request('account_id')])->links('pagination::bootstrap-4') }} --}}

        @endif
    </div>
</div>

{{-- <div class="tab-content">
    <div id="shipping_addresses" class="card-body p-0 tab-pane fade show" role="tabpanel"
        aria-labelledby="shipping_addresses_tab">
        @include('clientsandsuppliers::Client.empty-data')
    </div>
</div> --}}
