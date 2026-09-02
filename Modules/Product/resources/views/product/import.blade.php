@extends('layouts.app')

@section('content')
@viteReactRefresh
@vite('resources/components/App.jsx')

<div id="root" type="importProduct"
  data-type="products"
  category-url="{{ json_encode(route('category.index'))}}"
  template-url="{{'/assets/media/svg/files/product.xlsx'}}"
  ems-can="{{ \Modules\Product\Support\ProductAccess::uiJson('importProduct') }}"
  dir="{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"></div>

@endsection