<div class="card-toolbar ">
    <!--begin::Tab nav-->
    <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold" role="tablist">
        {{-- <li class="nav-item" role="presentation">
            <a id="payment_info_tab" class="nav-link justify-content-center text-active-gray-800 active"
                data-bs-toggle="tab" role="tab" href="#payment_info" aria-selected="true">
                @lang('sales::lang.payment_info')
            </a>
        </li> --}}
        <li class="nav-item" role="presentation">
            <a id="nots_tab" class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab"
                role="tab" href="#nots" aria-selected="false" tabindex="-1">
                @lang('sales::lang.invoice_note')

            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a id="attachments_tab" class="nav-link justify-content-center text-active-gray-800 " data-bs-toggle="tab"
                role="tab" href="#attachments" aria-selected="false" tabindex="-1">
                @lang('sales::lang.attachments')
            </a>
        </li>

    </ul>
    <!--end::Tab nav-->
</div>

{{-- <div class="tab-content">
    <div id="payment_info" class="card-body p-0 tab-pane fade show active" role="tabpanel"
        aria-labelledby="payment_info_tab">

        @include('sales::sell.create.payment')


    </div>
</div> --}}

<div class="tab-content ">
    <div id="nots" class="card-body p-0 tab-pane fade show active" role="tabpanel"
        aria-labelledby="nots_tab">

        @include('sales::sell.create.nots')

    </div>
</div>

<div class="tab-content">
    <div id="attachments" class="card-body p-0 tab-pane fade show " role="tabpanel" aria-labelledby="attachments_tab">

        @include('sales::sell.create.attachments')

    </div>
</div>
