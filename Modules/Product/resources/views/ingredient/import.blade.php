@extends('layouts.app')

@section('content')
@viteReactRefresh
@vite('resources/components/App.jsx')

<div id="root" type="importIngredient"
  data-type="ingredients"
  template-url="{{'/assets/media/svg/files/ingredient.xlsx'}}"
  back-url="{{ route('ingredient.index') }}"
  ems-can="{{ \Modules\Product\Support\ProductAccess::uiJson('ingredient') }}"
  dir="{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"></div>

@endsection
