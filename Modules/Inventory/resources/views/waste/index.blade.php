@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="waste" 
	  list-url="{{json_encode(value: route('wasteList'))}}"
	  dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"></div>

@endsection
