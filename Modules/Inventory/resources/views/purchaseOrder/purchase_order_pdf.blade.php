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
    <title>Invoice</title>
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
        }
        table.products th {
            color: #ffffff;
            padding: 0.5rem;
        }
        table tr.items {
            background-color: rgb(241 245 249);
        }
        table tr.items td {
            padding: 0.5rem;
        }
        .total {
            text-align: {{$align}};
            margin-top: 1rem;
            font-size: 0.875rem;
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
                <h2>{{__('reports.no')}}: {{ $data['no'] }}</h2>
            </td>
        </tr>
    </table>
 
    <div class="margin-top">
        <table class="w-full">
            <tr>
                <td class="w-half">
                    <div><h4>{{__('reports.to')}}: {{ $local =='ar' ? $data['vendor']->name_ar : $data['vendor']->name_en }}</h4></div>
                </td>
                <td class="w-half">
                    <div><h4>{{__('reports.date')}}: {{ (new DateTime($data['op_date']))->format('Y-m-d') }}</h4></div>
                </td>
            </tr>
        </table>
    </div>
 
    <div class="margin-top">
        <table class="products">
            <tr>
                <th>{{__('reports.serial')}}</th>
                <th>{{__('reports.itemDescription')}}</th>
                <th>{{__('reports.qty')}}</th>
                <th>{{__('reports.unit')}}</th>
                <th>{{__('reports.price')}}</th>
                <th>{{__('reports.totalPrice')}}</th>
            </tr>
            
                @foreach($data['items'] as $key =>$item)
                    <tr class="items">
                    <td>
                        {{ $key +1 }}
                    </td>
                    <td>
                        {{ $local =='ar' ? $item['product']["name_ar"] : $item['product']["name_en"] }}
                    </td>
                    <td>
                        {{ $item['qty'] }}
                    </td>
                    <td>
                        {{ $item['unit']["unit1"] }}
                    </td>
                    <td>
                        {{ $item['cost'] }}
                    </td>
                    <td>
                        {{ $item['total'] }}
                    </td>
                    </tr>
                @endforeach
           
        </table>
    </div>
 
    <div class="total">
        {{__('reports.subTotal')}}: {{ $data['itemTotal'] }}
    </div>
    <div class="total">
        {{__('reports.tax')}}: {{ isset($data['tax']) ? $data['tax'] : 0}}
    </div>
    <div class="total">
        {{__('reports.total')}}: {{ $data['itemTotal'] + (isset($data['tax']) ? $data['tax'] : 0) }}
    </div>
    <div class="total">
        {{__('reports.miscAmount')}}: {{ isset($data['misc_amount']) ? $data['misc_amount'] : 0 }}
    </div>
    <div class="total">
        {{__('reports.shippingAmount')}}: {{ isset($data['shipping_amount']) ? $data['shipping_amount'] : 0 }}
    </div>
    <div class="total">
        {{__('reports.grandTotal')}}: {{ isset($data['total']) ? $data['total'] : 0 }}
    </div>
 
    <div class="margin-top">
        <table class="w-full">
            <tr>
                <td class="w-half">
                    <div><h4>{{__("reports.buyerSig")}}: _______________________</h4></div>
                </td>
                <td class="w-half">
                    <div><h4>{{__('reports.date')}}: _______________________</h4></div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>