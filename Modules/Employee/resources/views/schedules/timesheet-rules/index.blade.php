@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees_working_hours'))

@section('content')
    <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-lg-n2 me-auto"
        role="tablist">
        <!--begin:::Tab item-->
        <li class="nav-item" role="presentation">
            <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_ecommerce_sales_order_summary"
                aria-selected="false" role="tab" tabindex="-1">Order Summary</a>
        </li>
        <!--end:::Tab item-->
        <!--begin:::Tab item-->
        <li class="nav-item" role="presentation">
            <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab"
                href="#kt_ecommerce_sales_order_history" aria-selected="true" role="tab">Order History</a>
        </li>
        <!--end:::Tab item-->
    </ul>
    {{-- <x-cards.card>

    </x-cards.card> --}}
@endsection

@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script type="text/javascript" src="vfs_fonts.js"></script>
    <script></script>
@endsection
