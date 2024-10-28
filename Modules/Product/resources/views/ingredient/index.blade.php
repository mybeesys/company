@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="ingredient" 
	    list-url="{{json_encode(route('ingredientList'))}}"
	    attribute-url="{{ json_encode(route('ingredient.store'))}}"
        localization-url ="{{json_encode(route('localization'))}}"
	    dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"  
	  ></div>

@endsection
