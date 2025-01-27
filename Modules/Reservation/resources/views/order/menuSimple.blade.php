@extends('layouts.menu')
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
        .order-header {
          background-color: #f4f4ff;
          text-align: center;
          padding: 20px;
          font-weight: bold;
          color: #5a4fcf;
          font-size: 1.5rem;
        }
        .order-summary {
          background-color: #f9f9f9;
          padding: 15px;
          border-radius: 8px;
        }
        .btn-place-order {
          background-color: #d1b5f9;
          border-color: #d1b5f9;
          color: white;
        }
        .btn-place-order:hover {
          background-color: #bba0e0;
          border-color: #bba0e0;
        }
        .quantity-controls input {
          max-width: 60px;
          text-align: center;
        }
        .custom-tabs .nav-link {
          border: none; /* Remove default border */
          color: #6c757d; /* Inactive tab color */
          padding: 0.5rem 1rem; /* Adjust spacing */
          position: relative;
        }

        .custom-tabs .nav-link.active {
          color: #7b2cf1; /* Active tab color */
          font-weight: bold;
        }

        .custom-tabs .nav-link.active::after {
          content: '';
          display: block;
          width: 100%;
          height: 2px;
          background-color: #7b2cf1; /* Line color */
          position: absolute;
          bottom: -2px; /* Adjust line placement */
          left: 0;
        }

        .custom-tabs {
          border-bottom: 1px solid #dee2e6; /* Light border under tabs */
        }
      </style>
@stop
@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
   						
      <div id="root" type="menuSimple" 
      info="{{json_encode($info)}}"
      list-url="{{json_encode(route('order.products'))}}"
      blank-url ='/assets/media/svg/files/blank-image.svg'
	    dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"  
	  ></div>

@endsection
