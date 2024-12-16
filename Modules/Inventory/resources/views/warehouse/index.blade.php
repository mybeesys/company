@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="warehouse" 
	  list-url="{{json_encode(value: route('warehouselist'))}}"
	  dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"
    warehouse-url="{{ json_encode(route('warehouse.store'))}}"
    ></div>

@endsection
