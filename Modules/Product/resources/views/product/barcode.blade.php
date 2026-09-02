@viteReactRefresh
@vite('resources/components/App.jsx')

@extends('layouts.app')
@section('css')
<style>
      @media print {
  body {
    margin: 0;
    padding: 0;
  }

  #root {
    width: 210mm;
    height: 297mm;
    background-color: white;
  }
}
</style>
@stop

@section('content')

   						
      <div id="root" type="productBarcode" ems-can="{{ json_encode(['print' => \Modules\Product\Support\ProductAccess::can(\Modules\Product\Support\ProductPermissions::BARCODE_PRINT)]) }}"></div>

@endsection