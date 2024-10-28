@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="custommenu" 
	  list-url="{{json_encode(route('customMenuList'))}}"
	  customMenu-url="{{ json_encode(route('customMenu.store'))}}"
	  application-type-url = "{{json_encode(route('application-type-values'))}}"
    mode-url = "{{json_encode(route('mode-values'))}}"
    localization-url ="{{json_encode(route('localization'))}}"
    station-url ="{{json_encode(route('stationList'))}}"
	  dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"></div>

@endsection
