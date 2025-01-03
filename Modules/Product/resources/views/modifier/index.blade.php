@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="modifier" 
	    list-url="{{json_encode(route('modifierClassList'))}}"
	    modifier-url="{{ json_encode(route('modifier.store'))}}"
      modifierClass-url="{{ json_encode(route('modifierClass.store'))}}"
      listTax-url ="{{json_encode(route('taxList'))}}"
	    dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"  
	  ></div>

@endsection
