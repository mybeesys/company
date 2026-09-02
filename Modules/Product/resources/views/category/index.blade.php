@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')

      <div id="root" type="category"
	  product-crud-url="{{ json_encode(route('product.index'))}}"
	  list-url="{{json_encode(route('categoryList'))}}"
	  category-url="{{ json_encode(route('category.store'))}}"
	  subcategory-url="{{ json_encode(route('subcategory.store'))}}"
	  product-url="{{ json_encode(route('product.store'))}}"
      product-permission="{{ $product_permission }}"
      ems-can="{{ \Modules\Product\Support\ProductAccess::uiJson('category', 'subcategory', 'product') }}"
	  dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"></div>

@endsection
