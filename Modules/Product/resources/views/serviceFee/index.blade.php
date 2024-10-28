@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root"  type="serviceFee" 
    localization-url ="{{json_encode(route('localization'))}}"
    list-url ="{{json_encode(route('serviceFeesTree'))}}"
    serviceFee-url="{{ json_encode(route('serviceFee.store'))}}"
	  dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"></div>

@endsection
