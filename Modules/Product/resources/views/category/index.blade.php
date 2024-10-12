@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="category-root" 
	  product-crud-url="{{ json_encode(route('product.index'))}}"
	  list-url="{{json_encode(route('categoryList'))}}"
	  category-url="{{ json_encode(route('category.store'))}}"
	  subcategory-url="{{ json_encode(route('subcategory.store'))}}"
	  product-url="{{ json_encode(route('product.store'))}}"
	  localization-url ="{{json_encode(route('localization'))}}"
	  dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"></div>

@endsection
