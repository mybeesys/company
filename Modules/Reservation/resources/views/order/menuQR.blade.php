@extends('layouts.app')
@section('css')
    <style>
      .title {
        font-size: 16px;
        font-weight: bold; /* Bold the "Lounge" text */
        color: #343a40; /* Dark gray color */
        padding-left: 10px;
      }
      .card-body {
        padding: 1rem!important; /* Adjust as needed, e.g., 0.5rem, 2rem, etc. */
      }
      .custom-btn {
          border: 1px solid #d1b5f9;
          background-color: transparent;
          color: #d1b5f9;
          padding: 5px 15px;
          font-size: 14px;
          border-radius: 5px;
          cursor: pointer;
          transition: all 0.3s ease;
        }

        .custom-btn:hover {
          background-color: #f4ebff;
          color: #7a41c5;
          border-color: #7a41c5;
        }
      </style>
@stop
@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="menuQR" 
      list-url="{{json_encode(route('order.products'))}}"
      logo-url ='/assets/media/logos/1-01.png'
	    dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"  
	  ></div>

@endsection
