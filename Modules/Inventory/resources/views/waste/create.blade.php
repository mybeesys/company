@extends('layouts.app')

@section('title', __('menuItemLang.waste'))
@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')

    <div class="container-fluid py-3">
        <div id="root" type="wasteedit"
            waste="{{ json_encode($waste) }}"
            waste-url="{{ json_encode(route('storeWaste')) }}"
            dir="{{ app()->getLocale() == 'en' ? 'ltr' : 'rtl' }}"></div>
    </div>
@endsection
