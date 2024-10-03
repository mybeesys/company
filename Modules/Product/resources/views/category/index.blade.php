@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/js/app.jsx')
   						
      <div id="react-root" 
	  list-url="{{json_encode(route('categoryList'))}}"
	  category-url="{{ json_encode(route('category.store'))}}"
	  subcategory-url="{{ json_encode(route('subcategory.store'))}}"
	  product-url="{{ json_encode(route('product.store'))}}"></div>

@endsection
