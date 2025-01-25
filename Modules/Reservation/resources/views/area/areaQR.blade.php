@extends('layouts.app')
@section('css')
    <style>
      .title {
        font-size: 16px;
        font-weight: bold; /* Bold the "Lounge" text */
        color: #343a40; /* Dark gray color */
        padding-left: 10px;
      }

      .badge {
        display: inline-block;
        padding: 5px 10px; /* Adjust padding for size */
        background-color: #f8f9fa; /* Light gray background */
        border: 1px solid #ddd; /* Subtle border */
        border-radius: 5px; /* Rounded corners */
        font-size: 14px; /* Adjust font size */
        font-weight: bold; /* Bold text */
        color: #343a40; /* Text color */
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        text-align: center; /* Center the text */
      }
      .table-id {
        background-color: #e7f3ff; /* Light blue background */
        color: #004085; /* Dark blue text */
        font-weight: bold; /* Bold text */
        font-size: 14px; /* Adjust font size */
        border-radius: 5px; /* Rounded corners for the badge */
        text-align: center; /* Center align text */
        
      }

      .table-seats {
        font-size: 14px; /* Adjust font size */
        color: #6c757d; /* Gray color for seats info */
        font-weight: bold; /* Make text bold */
        text-align: end;
}
    </style>
@stop
@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="areaQR" 
      list-url="{{json_encode(route('areaList'))}}"
	    dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"  
	  ></div>

@endsection
