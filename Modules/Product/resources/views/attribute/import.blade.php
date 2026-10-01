@extends('layouts.app')

@section('content')
@viteReactRefresh
@vite('resources/components/App.jsx')

<div id="root" type="importAttribute"
  data-type="attributes"
  template-url="{{'/assets/media/svg/files/attribute.xlsx'}}"
  back-url="{{ route('attribute.index') }}"
  ems-can="{{ \Modules\Product\Support\ProductAccess::uiJson('attribute') }}"
  dir="{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"></div>

@endsection
