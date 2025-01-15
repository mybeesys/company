@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="openInventoryImport"
      data-type = "openInventory"
	  base-url="{{ json_encode(route('productInventory.index'))}}"
    template-url="{{'/assets/media/svg/files/inventories.xlsx'}}"
	  dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"></div>

@endsection
