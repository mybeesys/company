@extends('layouts.app')

@section('title', __('menuItemLang.waste'))
@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')

    <div class="container-fluid py-3">
        <div id="root" type="waste"
            list-url="{{ json_encode(route('wasteList')) }}"
            ems-can="{{ \Modules\Inventory\Support\InventoryAccess::uiJson('waste') }}"
            dir="{{ app()->getLocale() == 'en' ? 'ltr' : 'rtl' }}"></div>
    </div>
@endsection
