@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="modifier" 
	    list-url="{{json_encode(route('modifierClassList'))}}"
	    modifier-url="{{ json_encode(route('modifier.store'))}}"
      modifierClass-url="{{ json_encode(route('modifierClass.store'))}}"
      localization-url ="{{json_encode(route('localization'))}}"
	    dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"  
	  ></div>

@endsection
