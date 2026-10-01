@extends('layouts.app')

@section('content')
@viteReactRefresh
@vite('resources/components/App.jsx')

<div id="root" type="importModifier"
  data-type="modifiers"
  template-url="{{'/assets/media/svg/files/modifier.xlsx'}}"
  back-url="{{ route('modifier.index') }}"
  ems-can="{{ \Modules\Product\Support\ProductAccess::uiJson('modifier') }}"
  dir="{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"></div>

@endsection
