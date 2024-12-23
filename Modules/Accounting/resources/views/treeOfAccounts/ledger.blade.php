@extends('layouts.app')

@section('title', __('accounting::lang.ledger'))
@section('css')
    <style>
        #kt_timeline_widget_3_tab_content_4>div:nth-child(3)>span {
            min-height: 40px !important;
        }

        .empty-content {
            display: grid;
            justify-content: center;
            text-align: center;
            gap: 11px;
            padding-bottom: 37px;
        }
    </style>
@stop
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-6">
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <h1>

                        @lang('accounting::lang.ledger') - {{app()->getLocale() == 'ar'?$account->name_ar: $account->name_en }}

                    </h1>
                </div>
            </div>
            <div class="col-6" style="justify-content: end;display: flex;">
                <div class="row">


                    <div class="col-2">
                        @if ($previous)
                            <a href="{{ route('ledger', ['account_id' => $previous->id]) }}" class="btn btn-primary "
                                style="padding: 5px;
                                border-radius: 50%;"><i
                                    @if (app()->getLocale() == 'en') class="ki-outline ki-arrow-left fs-1 p-0" @endif
                                    @if (app()->getLocale() == 'ar') class="ki-outline ki-arrow-right fs-1 p-0" @endif></i></a>
                        @endif
                    </div>
                    <div class="col-8">

                        <select id="accounts" class="form-select form-select-solid select-2" name="id">

                            @foreach ($accountingAccount as $_account)
                                <option value="{{ $_account->id }}" @if ($account->id == $_account->id) selected @endif>
                                    @if (app()->getLocale() == 'ar')
                                        {{ $_account->name_ar }}
                                    @else
                                        {{ $_account->name_en }}
                                    @endif -

                                    {{ $_account->gl_code }}
                                </option>
                            @endforeach

                        </select>
                    </div>
                    <div class="col-2">

                        @if ($next)
                            <a href="{{ route('ledger', ['account_id' => $next->id]) }}" class="btn btn-primary"
                                style="padding: 5px;
                                border-radius: 50%;"><i
                                    @if (app()->getLocale() == 'en') class="ki-outline ki-arrow-right fs-1 p-0" @endif
                                    @if (app()->getLocale() == 'ar') class="ki-outline ki-arrow-left fs-1 p-0" @endif></i></a>
                        @endif
                        {{-- </div> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="separator d-flex flex-center mb-5">
        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
    </div>

    <div class="tab-content mb-2 px-9 " @if (app()->getLocale() == 'ar') dir="rtl" @endif>


        <div class="tab-pane fade row show active" id="kt_timeline_widget_3_tab_content_4" role="tabpanel">

            <div class="d-flex col-10 align-items-center mb-3">
                <span data-kt-element="bullet"
                    class="bullet bullet-vertical d-flex align-items-center min-h-70px mh-100 me-4 bg-info"></span>
                <div class="flex-grow-1 me-5">
                    <div class="text-gray-800 fw-semibold fs-5">
                        @lang('accounting::lang.account_name'): @if (app()->getLocale() == 'ar')
                            {{ $account->name_ar }}
                        @else
                            {{ $account->name_en }}
                        @endif -
                        <span class="text-gray-500 fw-semibold fs-5">
                            {{ $account->gl_code }} </span>
                    </div>
                    <div class="text-gray-800 fw-semibold fs-5">
                        @lang('accounting::lang.account_primary_type'):
                        <span class="text-gray-500 fw-semibold fs-5">
                            @lang('accounting::lang.' . $account->account_primary_type) </span>
                    </div>

                    <div class="text-gray-800 fw-semibold fs-5">
                        @lang('accounting::lang.account_sub_type'):
                        <span class="text-gray-500 fw-semibold fs-5">
                            @if (app()->getLocale() == 'ar')
                                {{ $account->account_sub_type['name_ar'] }}
                            @else
                                {{ $account->account_sub_type['name_en'] }}
                            @endif
                        </span>
                    </div>

                </div>

                <div class="col">
                    <div class="row-cols">
                        <div class="text-gray-800 fw-semibold fs-5">
                            @lang('accounting::lang.sub_account_type'):
                            <span class="text-gray-500 fw-semibold fs-5">
                                @if ($account->account_sub_type['account_primary_type'])
                                    @lang('accounting::lang.' . $account->account_sub_type['account_primary_type'])
                            </span>
                        @else
                            --
                            @endif
                        </div>
                    </div>

                    <div class="row-cols">
                        <div class="text-gray-800 fw-semibold fs-5">
                            @lang('accounting::lang.account_category'):
                            <span class="text-gray-500 fw-semibold fs-5">
                                @if ($account->account_category)
                                    @lang('accounting::lang.' . $account->account_category)
                                @else
                                    --
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="text-gray-800 fw-semibold fs-5">
                        @lang('accounting::lang.account_type'):
                        <span class="text-gray-500 fw-semibold fs-4">
                            @if ($account->account_type)
                                @lang('accounting::lang.' . $account->account_type)
                            @else
                                --
                            @endif
                        </span>
                    </div>

                </div>


            </div>


            <div class="d-flex align-items-center mb-6">

                <span data-kt-element="bullet"
                    class="bullet bullet-vertical d-flex align-items-center min-h-58px mh-100 me-4 bg-success"
                    style="height: 27px;"></span>



                <div class="flex-grow-1 me-5">


                    <div class="text-gray-800 fw-semibold fs-5">
                        @lang('accounting::lang.balance'):
                        <span class=" fw-semibold fs-2" style="color: #0945e9">
                            @format_currency($current_bal)</span>
                    </div>



                </div>


            </div>

        </div>
    </div>


    <div class="card mb-5 mb-xl-8" @if (app()->getLocale() == 'ar') dir="rtl" @endif>

        <div class="card-header border-0 pt-5">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold fs-3 mb-1">@lang('accounting::lang.account_transactions')</span>

                <span class="text-muted mt-1 fw-semibold fs-7">
                    @if (count($account_transactions) > 0)
                        {{ count($account_transactions) }} @lang('messages.transactions')
                    @endif

                </span>
            </h3>
            @if (count($account_transactions) > 0)
            <div class="card-toolbar">
                <div class="btn-group dropend">

                    <button type="button" style="background: transparent;adding: 2px 7px 8px 13px;border-radius: 6px;"
                        class="btn  dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cog" style="font-size: 1.4rem; color: #c59a00;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu" style=" width: max-content;padding: 10px;"
                        style="padding: 8px 15px;">
                        <li class="mb-5"
                            style="text-align: justify; border-bottom: 1px solid #00000029; padding:0.8rem;
                            ">
                            <span class="card-label fw-bold fs-6 mb-1 ">@lang('messages.settings')</span>
                        </li>



                        <li>

                            <div class="menu-item-custom ">
                                <a href= "{{ url('/print-ledger', $account->id) }}" class="btn">@lang('accounting::fields.print')</a>
                            </div>
                        </li>

                        <li>
                            <div class="menu-item-custom ">
                                <a href= "{{ url('/ledger-export-pdf', $account->id) }}"
                                    class="btn">@lang('general.export_as_pdf')</a>
                            </div>
                        </li>


                        <li>
                            <div class="menu-item-custom ">
                                <a href= "{{ url('/ledger-export-excel', $account->id) }}"
                                    class="btn">@lang('general.export_as_excel')</a>
                            </div>
                        </li>



                    </ul>
                </div>
            </div>
@endif

        </div>



        @if (count($account_transactions) == 0)
            <div class="empty-content">
                <img src="/assets/media/illustrations/empty-content.svg" class=" w-200px" alt="">
                <span class="text-gray-500 fw-semibold fs-6" style="margin: 7px -34px;">
                    @lang('messages.no_account_transactions')</span>
            </div>
        @else
            <div class="card-body py-3">
                <div class="table-responsive">
                    <table class="table align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold  text-muted bg-light">
                                <th class="min-w-125px ">@lang('accounting::lang.transaction_number')</th>
                                <th class="min-w-80px">@lang('accounting::lang.operation_date')</th>
                                <th class="min-w-125px">@lang('accounting::lang.transaction')</th>
                                <th class="min-w-125px">@lang('accounting::lang.cost_center')</th>
                                <th class="min-w-200px">@lang('accounting::lang.added_by')</th>
                                <th class="min-w-150px">@lang('accounting::lang.debit')</th>
                                <th class="min-w-150px">@lang('accounting::lang.credit')</th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($account_transactions as $transactions)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex justify-content-start flex-column">
                                                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                                    @if ($transactions->sub_type =='journal_entry')
                                                    {{ $transactions->accTransMapping->ref_no }}</a>

                                                    @else
                                                    {{ $transactions->transaction->ref_no }}</a>


                                                    @endif

                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <a
                                            class="text-gray-900 fw-bold text-hover-primary d-block mb-1 fs-7">{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $transactions->operation_date)->format('d/m/Y h:i A') }}</a>
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
                                            {{ $transactions->createdBy->name }}</a>

                                    </td>

                                    <td>

                                        @if ($transactions->type == 'debit')
                                            <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                                {{ $transactions->amount }}</a>
                                        @else
                                            <span class="text-muted fw-semibold text-muted d-block fs-4 mt-1">
                                                --
                                            </span>
                                        @endif

                                    </td>
                                    <td>
                                        <span class="text-muted fw-semibold text-muted d-block fs-4 mt-1">
                                            @if ($transactions->type == 'credit')
                                                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                                    {{ $transactions->amount }}</a>
                                            @else
                                                <span class="text-muted fw-semibold text-muted d-block fs-4 mt-1">
                                                    --
                                                </span>
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>


                </div>

            </div>
            {{ $account_transactions->appends(['account_id' => request('account_id')])->links('pagination::bootstrap-4') }}

        @endif
    </div>
@endsection
@section('script')


    <script>
        $(document).ready(function() {
            $('#accounts').on('change', function() {
                var selectedValue = this.value;
                url = '{{ url('ledger') }}?account_id=' + selectedValue;


                window.location.href = url;
            });

            $('#accounts').select2();
        });
    </script>
@stop
