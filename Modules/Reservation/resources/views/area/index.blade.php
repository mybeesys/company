@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="area" 
      list-url="{{json_encode(route('areaMiniList'))}}"
      area-url="{{ json_encode(route('area.store'))}}"
	    dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"  
	  ></div>

@endsection
