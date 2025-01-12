@viteReactRefresh
@vite('resources/components/App.jsx')

@extends('layouts.app')
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }

        .custom-width {
            min-width: 60%;
            width: 60%;
        }

        .custom-height {
            height: 35px;
            width: 60%;
        }

        .custom-input {
            height: 35px;
        }

        .custom-header {
            background-color: #f1f1f4 !important;
            min-height: 50px !important;
        }

        .me-3 {
            margin-right: 0 !important;
        }
    </style>
@stop

@section('content')

   						
      <div id="root" type="modifierdetail" 
        modifier-url="{{ json_encode(route('modifier.store'))}}"
        modifier="{{json_encode($modifier)}}"
        listTax-url ="{{json_encode(route('taxList'))}}"
        listRecipe-url="{{json_encode(route('listRecipebyProduct'))}}"
        ingredientProductUrl-url="{{json_encode(route('ingredientProductList'))}}"
        dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}">
     </div>

@endsection
