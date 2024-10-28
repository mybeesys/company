@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="attribute"
	    list-url="{{json_encode(route('attributeClassList'))}}"
	    attribute-url="{{ json_encode(route('attribute.store'))}}"
      attributeClass-url="{{ json_encode(route('attributeClass.store'))}}"
      localization-url ="{{json_encode(route('localization'))}}"
	    dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"  
	  ></div>

@endsection
