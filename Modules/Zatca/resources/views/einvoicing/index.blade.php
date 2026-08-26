@extends('layouts.app')

@section('title', __('zatca::lang.einvoicing_page_title'))

@section('css')
@include('zatca::partials.ui-styles')
@endsection

@section('content')
@php
    $activeTab = $activeTab ?? 'send';
    $listingRoute = $zatcaListingRoute ?? 'zatca.einvoicing.index';
@endphp

<div class="container-fluid zatca-settings py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="fs-2 fw-bold mb-1">{{ __('zatca::lang.einvoicing_page_title') }}</h1>
            <p class="text-muted mb-0">{{ __('zatca::lang.einvoicing_page_subtitle') }}</p>
        </div>
        <a href="{{ route('zatca.settings.edit', ['tab' => 'connection']) }}" class="btn btn-light-primary btn-sm">
            <i class="fa fa-cog me-1"></i>
            {{ __('zatca::lang.menu_card') }}
        </a>
    </div>

    @include('zatca::partials.sync-feedback')

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex flex-column flex-row-fluid gap-5">
        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link justify-content-center text-active-gray-800 {{ $activeTab === 'send' ? 'active' : '' }}"
                   href="{{ route($listingRoute, array_filter(['tab' => 'send', 'zatca_status' => request('zatca_status')])) }}">
                    {{ __('zatca::lang.tab_send_sell') }}
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link justify-content-center text-active-gray-800 {{ $activeTab === 'returns' ? 'active' : '' }}"
                   href="{{ route($listingRoute, array_filter(['tab' => 'returns', 'zatca_return_status' => request('zatca_return_status')])) }}">
                    {{ __('zatca::lang.tab_send_returns') }}
                </a>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade {{ $activeTab === 'send' ? 'show active' : '' }}"
                 id="zatca_send_sell_tab"
                 role="tabpanel">
                @include('zatca::settings.partials.send-sell-invoice')
            </div>

            <div class="tab-pane fade {{ $activeTab === 'returns' ? 'show active' : '' }}"
                 id="zatca_send_returns_tab"
                 role="tabpanel">
                @include('zatca::settings.partials.send-sell-returns')
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    const select2Options = {
        width: '100%',
        dir: document.documentElement.getAttribute('dir') || 'ltr',
    };

    function initSelect2(scope) {
        $(scope).find('select.select-2').each(function () {
            const $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.select2(select2Options);
        });
    }

    if ($('#zatca_send_sell_tab').hasClass('active')) {
        initSelect2('#zatca_send_sell_tab');
    }
    if ($('#zatca_send_returns_tab').hasClass('active')) {
        initSelect2('#zatca_send_returns_tab');
    }
})();
</script>
@include('zatca::settings.partials.send-scripts')
@endsection
