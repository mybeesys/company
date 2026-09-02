@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="transfer" 
	  list-url="{{json_encode(value: route('transferList'))}}"
      ems-can="{{ \Modules\Inventory\Support\InventoryAccess::uiJson('transfer') }}"
	  dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"></div>

@endsection
