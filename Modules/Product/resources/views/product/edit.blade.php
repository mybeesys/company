@viteReactRefresh
@vite('resources/components/App.jsx')

@extends('layouts.app')



@section('content')

   						
      <div id="product-root" 
	    product-url="{{ json_encode(route('product.store'))}}"
        product="{{json_encode($product)}}"
        image-url="{{!$product->image_path ?  '/assets/media/svg/files/blank-image.svg' : asset('storage/' . $product->image_path)}}"
        localization-url ="{{json_encode(route('localization'))}}"
        dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}">
     </div>

@endsection
