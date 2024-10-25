@viteReactRefresh
@vite('resources/components/App.jsx')

@extends('layouts.app')



@section('content')

   						
      <div id="custommenuedit-root" 
        localization-url ="{{json_encode(route('localization'))}}"
        custommenu="{{json_encode(new stdClass())}}"
        dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}">
     </div>

@endsection