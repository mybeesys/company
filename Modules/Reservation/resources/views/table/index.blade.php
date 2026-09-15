@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')

     <div id="root"
     type="table"
     ems-can="{{ \Modules\General\Support\SettingAccess::uiJson('tables') }}"

     table-url="{{ json_encode(route('table.store')) }}"

     list-url="{{ json_encode(route('tableList')) }}"
     listTableStatus-url="{{ json_encode(route('table-status-type-values')) }}"
     dir="{{ app()->getLocale() == 'en' ? 'ltr' : 'rtl' }}"
></div>

@endsection
