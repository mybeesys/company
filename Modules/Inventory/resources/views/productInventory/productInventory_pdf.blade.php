<!doctype html>
@php
    $local = session()->get('locale');
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
    $align = $local == 'ar' ? 'left' : 'right';
@endphp
<html lang="en" dir="{{$dir}}">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Inventory</title>
    <link rel="stylesheet" href="{{ asset('pdf.css') }}" type="text/css">
    <style>
       * {
            font-family: DejaVu Sans !important;
        }

        body {
            font-size: 16px;
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', sans-serif;
            padding: 10px;
            margin: 10px;

            color: #777;
        }
        h4 {
            margin: 0;
        }
        .w-full {
            direction: {{$dir}};
            width: 100%;
        }
        .w-half {
            width: 50%;
        }
        .margin-top {
            margin-top: 1.25rem;
        }
        .footer {
            font-size: 0.875rem;
            padding: 1rem;
            background-color: rgb(241 245 249);
        }
        table {
            width: 100%;
            border-spacing: 0;
        }
        table.products {
            font-size: 0.875rem;
        }
        table.products tr {
            background-color: rgb(96 165 250);
            border: 1px solid #D3D3D3; /* Adds borders around cells */
        }
        table.products th {
            color: #ffffff;
            padding: 0.5rem;
            border: 1px solid #D3D3D3; /* Adds borders around cells */
        }
        table tr.items {
            background-color: rgb(241 245 249);
        }
        table tr.items td {
            border: 1px solid #D3D3D3; /* Adds borders around cells */
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        .level-0 {
           
        }
        .level-1 {
            padding-{{$dir == 'rtl' ? 'right' : 'left'}}: 20px;
        }
        .level-2 {
            padding-{{$dir == 'rtl' ? 'right' : 'left'}}: 30px;
        }
        .level-3 {
            padding-{{$dir == 'rtl' ? 'right' : 'left'}}: 40px;
        }
        .level-4 {
            padding-{{$dir == 'rtl' ? 'right' : 'left'}}: 50px;
        }
        .level-5 {
            padding-{{$dir == 'rtl' ? 'right' : 'left'}}: 60px;
        }
    </style>
     <script>
        window.onload = function() {
            window.print();
        };
    </script>
</head>
<body>
    <table class="w-full">
        <tr>
            <td class="w-half">
                <img src="data:image/jpeg;base64,{{ $image }}" alt="larrravel daily" width="200" />
            </td>
            <td class="w-half">
                <h2>{{ $type == 'p' ? __('reports.productInventory') : __('reports.ingredientInventory') }}</h2>
            </td>
        </tr>
    </table>
 
    <div class="margin-top">
        <table class="products">
            <tr>
                <!-- <th>{{__('reports.serial')}}</th> -->
                <th>{{__('reports.description')}}</th>
                <th>{{__('reports.qty')}}</th>
            </tr>
            
            @include('inventory::productInventory.productInventory_pdf_detail', 
                    [
                        'data' => $data, 
                        'local' => $local,
                        'level' => $level
                    ])
           
        </table>
    </div>
 
    
</body>
</html>