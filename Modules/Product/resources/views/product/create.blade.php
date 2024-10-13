@viteReactRefresh
@vite('resources/components/App.jsx')

@extends('layouts.app')



@section('content')

   						
      <div id="product-root" 
        category-url ="{{ json_encode(route('category.index'))}}"
	      product-url="{{ json_encode(route('product.store'))}}"
        product="{{json_encode(new stdClass())}}"
        listCategory-url ="{{json_encode(route('minicategorylist'))}}"
        listSubCategory-url="{{json_encode(route('minisubcategorylist'))}}"
        image-url="{{'/assets/media/svg/files/blank-image.svg'}}"
        localization-url ="{{json_encode(route('localization'))}}"
        dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}">
     </div>

@endsection
