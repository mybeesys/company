@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('Modules/Product/resources/components/product_component.jsx')
   						
      <div id="product-root" 
	    product-url="{{ json_encode(route('product.store'))}}"
        product="{{json_encode($product)}}">
     </div>

@endsection
