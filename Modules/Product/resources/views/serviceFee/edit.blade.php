@viteReactRefresh
@vite('resources/components/App.jsx')

@extends('layouts.app')



@section('content')

   						
      <div id="root" type="servicefeeedit" 
      serviceFee="{{json_encode($serviceFee)}}"
        localization-url ="{{json_encode(route('localization'))}}"
        dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}">
     </div>

@endsection