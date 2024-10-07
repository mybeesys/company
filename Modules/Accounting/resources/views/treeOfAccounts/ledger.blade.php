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
                        {{-- @if (app()->getLocale() == 'en') --}}
                            @lang('accounting::lang.ledger') - {{ $account->name_en }}
                        {{-- @endif --}}
                    </h1>
                </div>
            </div>
            <div class="col-6" style="justify-content: end;display: flex;">
                <h1>
                    {{-- @if (app()->getLocale() == 'ar') --}}
                        {{-- @lang('accounting::lang.ledger') - {{ $account->name_ar }} --}}
                    {{-- @endif --}}
                </h1>
            </div>
        </div>
    </div>

    <div class="separator d-flex flex-center mb-5">
        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
    </div>

    <div class="tab-content mb-2 px-9" @if (app()->getLocale() == 'ar') dir="rtl" @endif>


        <div class="tab-pane fade show active" id="kt_timeline_widget_3_tab_content_4" role="tabpanel">

            <div class="d-flex align-items-center mb-3">
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


            </div>

            <div class="d-flex align-items-center mb-3">

                <span data-kt-element="bullet"
                    class="bullet bullet-vertical d-flex align-items-center min-h-65px mh-100 me-4 bg-warning"></span>

                <div class="flex-grow-1 me-5">
                    <!--begin::Time-->
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

                {{-- <a href="#" class="btn btn-sm btn-light" data-bs-toggle="modal"
                    data-bs-target="#kt_modal_create_project">View</a>
          --}}
            </div>

            <div class="d-flex align-items-center mb-6">

                <span data-kt-element="bullet"
                    class="bullet bullet-vertical d-flex align-items-center min-h-70px mh-100 me-4 bg-success"></span>



                <div class="flex-grow-1 me-5">


                    <div class="text-gray-800 fw-semibold fs-5">
                        @lang('accounting::lang.balance'):
                        <span class=" fw-semibold fs-2" style="color: #0945e9">
                            {{ $current_bal??'--' }} </span>
                    </div>



                </div>
                {{--
                <a href="#" class="btn btn-sm btn-light" data-bs-toggle="modal"
                    data-bs-target="#kt_modal_create_project">View</a> --}}

            </div>

        </div>
    </div>


    <div class="card mb-5 mb-xl-8" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
        <!--begin::Header-->
        <div class="card-header border-0 pt-5">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold fs-3 mb-1">@lang('accounting::lang.account_transactions')</span>

                <span class="text-muted mt-1 fw-semibold fs-7">
                    @if (count($account_transactions) > 0)
                        {{ count($account_transactions) }} @lang('messages.transactions')
                    @endif

                </span>
            </h3>
            <div class="card-toolbar">

                <button type="button" class="btn btn-sm btn-icon btn-color-primary btn-active-light-primary"
                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                    <i class="ki-outline ki-category fs-6"></i> </button>


                {{-- <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px"
                    data-kt-menu="true">
                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <div class="menu-content fs-6 text-gray-900 fw-bold px-3 py-4">Quick Actions</div>
                    </div>
                    <!--end::Menu item-->

                    <!--begin::Menu separator-->
                    <div class="separator mb-3 opacity-75"></div>
                    <!--end::Menu separator-->

                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="#" class="menu-link px-3">
                            New Ticket
                        </a>
                    </div>
                    <!--end::Menu item-->

                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="#" class="menu-link px-3">
                            New Customer
                        </a>
                    </div>
                    <!--end::Menu item-->

                    <!--begin::Menu item-->
                    <div class="menu-item px-3" data-kt-menu-trigger="hover" data-kt-menu-placement="right-start">
                        <!--begin::Menu item-->
                        <a href="#" class="menu-link px-3">
                            <span class="menu-title">New Group</span>
                            <span class="menu-arrow"></span>
                        </a>
                        <!--end::Menu item-->

                        <!--begin::Menu sub-->
                        <div class="menu-sub menu-sub-dropdown w-175px py-4">
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3">
                                    Admin Group
                                </a>
                            </div>
                            <!--end::Menu item-->

                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3">
                                    Staff Group
                                </a>
                            </div>
                            <!--end::Menu item-->

                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3">
                                    Member Group
                                </a>
                            </div>
                            <!--end::Menu item-->
                        </div>
                        <!--end::Menu sub-->
                    </div>
                    <!--end::Menu item-->

                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <a href="#" class="menu-link px-3">
                            New Contact
                        </a>
                    </div>
                    <!--end::Menu item-->

                    <!--begin::Menu separator-->
                    <div class="separator mt-3 opacity-75"></div>
                    <!--end::Menu separator-->

                    <!--begin::Menu item-->
                    <div class="menu-item px-3">
                        <div class="menu-content px-3 py-3">
                            <a class="btn btn-primary  btn-sm px-4" href="#">
                                Generate Reports
                            </a>
                        </div>
                    </div>
                    <!--end::Menu item-->
                </div> --}}

            </div>
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
                                {{ $transactions->acc_trans_mapping }}
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex justify-content-start flex-column">
                                                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                                    {{ $transactions->accTransMapping->ref_no }}</a>

                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <a
                                            class="text-gray-900 fw-bold text-hover-primary d-block mb-1 fs-7">{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $transactions->operation_date)->format('d/m/Y h:i A') }}</a>
                                        {{-- <span class="text-muted fw-semibold text-muted d-block fs-7">Pending</span> --}}
                                    </td>

                                    <td>
                                        <span class="badge badge-light-primary fs-7">@lang('accounting::lang.' . $transactions->sub_type)</span>
                                        {{-- <a href="#"
                                        class="text-gray-900 fw-bold text-hover-primary d-block mb-1 fs-6">$5,400</a>
                                    <span class="text-muted fw-semibold text-muted d-block fs-7">Paid</span> --}}
                                    </td>

                                    <td>
                                        {{-- cost Center  --}}
                                        <span class="text-muted fw-semibold text-muted d-block fs-4">--</span>
                                    </td>

                                    <td>
                                        {{-- <span --}}
                                        {{-- class="text-muted fw-semibold text-muted d-block fs-4 mt-1">--</span> --}}
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
        @endif
    </div>
@endsection
