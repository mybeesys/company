@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="area" 
      list-url="{{json_encode(route('areaList'))}}"
	    table-url="{{ json_encode(route('table.store'))}}"
      area-url="{{ json_encode(route('area.store'))}}"
      listTableStatus-url ="{{json_encode(route('table-status-type-values'))}}"
	    dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"  
	  ></div>

@endsection
