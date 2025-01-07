@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="priceTier" 
	  list-url="{{json_encode(value: route('priceTierlist'))}}"
	  dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"
    priceTier-url="{{ json_encode(route('priceTier.store'))}}"
    ></div>

@endsection
