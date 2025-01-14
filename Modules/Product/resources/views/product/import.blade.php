@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="importProduct"
	  category-url="{{ json_encode(route('category.index'))}}"
    template-url="{{'/assets/media/svg/files/products.xlsx'}}"
	  dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"></div>

@endsection
