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

   						
      <div id="root" type="product"
        category-url ="{{ json_encode(route('category.index'))}}"
	    product-url="{{ json_encode(route('product.store'))}}"
        product="{{json_encode($product)}}"
        listCategory-url ="{{json_encode(route('minicategorylist'))}}"
        listTax-url ="{{json_encode(route('taxList'))}}"
        listSubCategory-url="{{json_encode(route('minisubcategorylist'))}}"
        listAttribute-url="{{json_encode(route('attributeClassList'))}}"
        getProductMatrix-url ="{{json_encode(route('getProductMatrix'))}}"
        image-url="{{!$product->image ?  null : asset($product->image)}}"
        blank-url ='/assets/media/svg/files/blank-image.svg'
        listModifier-url="{{json_encode(route('modifierClassList'))}}"
        listRecipe-url="{{json_encode(route('listRecipebyProduct'))}}"
        ingredientProductUrl-url="{{json_encode(route('ingredientProductList'))}}"
        dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}">
     </div>

@endsection
