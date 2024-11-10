@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root"  type="linkedCombo" 
    list-url ="{{json_encode(route('linkedComboList'))}}"
    linkedCombo-url="{{ json_encode(route('linkedCombo.store'))}}"
	  dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"></div>

@endsection
