@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root"  type="unit" 
      list-url ="{{json_encode(route('unitTree'))}}"
      Unit-url="{{ json_encode(route('unit.store'))}}"
	    dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"></div>

@endsection
