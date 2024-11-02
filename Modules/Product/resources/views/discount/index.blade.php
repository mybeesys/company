@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="discount" 
	    list-url="{{json_encode(route('discountList'))}}"
	    discount-url="{{ json_encode(route('discount.store'))}}"
      dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"  
	  ></div>

@endsection
