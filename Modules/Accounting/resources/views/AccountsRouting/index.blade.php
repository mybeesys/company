@extends('layouts.app')

@section('title', __('menuItemLang.accounts-routing'))
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }
    </style>
@stop
@section('content')
    <form id="accounts-routing" method="POST" action="{{ route('accounts-routing-store') }}">
        @csrf
        <div class="d-flex flex-row-fluid gap-5">
            <ul
                class="nav nav-tabs nav-pills rounded shadow-sm p-5 flex-row flex-md-column me-5 mb-3 mb-md-0 fs-6 min-h-450px">
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3 active" data-bs-toggle="tab" href="#sales-tab">
                        @lang('menuItemLang.sales')
                    </a>
                </li>
                <li class="nav-item w-md-200px me-0 py-1">
                    <a class="nav-link py-3" data-bs-toggle="tab" href="#purchases-tab">
                        @lang('menuItemLang.purchases')
                    </a>
                </li>
            </ul>

            <div class="tab-content w-100" id="mySubTabContent">
                <div class="tab-pane fade show active" id="sales-tab" role="tabpanel">
                    @include('accounting::AccountsRouting.sales.sales-tab')

                </div>
                <div class="tab-pane fade" id="purchases-tab" role="tabpanel">
                    @include('accounting::AccountsRouting.purchases.purchases-tab')
                </div>
            </div>
        </div>


        <button type="submit" style="border-radius: 6px;" class="btn btn-primary  w-200px">
            @lang('messages.save')
        </button>


    </form>


@stop

@section('script')
    <script>
        $(document).ready(function() {

            $("#client_account").select2();
            $("#client_type_route").select2();
            $("#sales_type_route").select2();
            $("#sales_account").select2();
            $("#sell_return_type_route").select2();
            $("#sell_return_account").select2();
            $("#discount_sales_account").select2();
            $("#discount_sales_type_route").select2();

            $("#suppliers_type_route").select2();
            $("#purchases_account").select2();
            $("#purchases_type_route").select2();
            $("#suppliers_account").select2();
            $("#purchases_return_type_route").select2();
            $("#purchases_return_account").select2();
            $("#discount_purchases_type_route").select2();
            $("#discount_purchases_account").select2();

            $("#vat_calculation_route").select2();
            $("#vat_calculation_account").select2();

            $("#total_amount_route").select2();
            $("#total_amount_account").select2();

            $("#amount_before_vat_route").select2();
            $("#amount_before_vat_account").select2();

            $("#discount_calculation_route").select2();
            $("#discount_calculation_account").select2();


        });
    </script>
@endsection
