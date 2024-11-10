@viteReactRefresh
@vite('resources/components/App.jsx')

@extends('layouts.app')



@section('content')

   						
      <div id="root" type="product"
        category-url ="{{ json_encode(route('category.index'))}}"
	      product-url="{{ json_encode(route('product.store'))}}"
        product="{{json_encode($product)}}"
        listCategory-url ="{{json_encode(route('minicategorylist'))}}"
        listSubCategory-url="{{json_encode(route('minisubcategorylist'))}}"
        listAttribute-url="{{json_encode(route('attributeClassList'))}}"
        getProductMatrix-url ="{{json_encode(route('getProductMatrix'))}}"
        image-url="{{!$product->image ?  '/assets/media/svg/files/blank-image.svg' : asset($product->image)}}"
        listModifier-url="{{json_encode(route('modifierClassList'))}}"
        listRecipe-url="{{json_encode(route('listRecipebyProduct'))}}"
        ingredientProductUrl-url="{{json_encode(route('ingredientProductList'))}}"
        dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}">
     </div>

@endsection
