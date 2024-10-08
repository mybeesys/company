@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/modifier/modifiertree.jsx')
   						
      <div id="modifier-root" 
	  list-url="{{json_encode(route('modifierClassList'))}}"
	  modifier-url="{{ json_encode(route('modifier.store'))}}"
      modifierClass-url="{{ json_encode(route('modifierClass.store'))}}"
	  ></div>

@endsection
