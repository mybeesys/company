@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="ingredient" 
	    list-url="{{json_encode(route('ingredientList'))}}"
      unitTypes-url="{{json_encode(route('unitTypeList'))}}"
      Ingredient-url="{{ json_encode(route('ingredient.store'))}}"
	    dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"  
	  ></div>

@endsection
