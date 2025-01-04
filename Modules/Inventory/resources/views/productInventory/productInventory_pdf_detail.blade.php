@foreach($data as $key => $item)
    <tr class="items">
        <!-- <td class="level-{{$level}}">{{ $level + $key + 1 }}</td> -->
        <td class="level-{{$level}}">{{ $local == 'ar' ? $item['name'] ?? $item['name_ar'] : $item['name_en'] }}</td>
        <td>{{ $item['qty'] ?? '' }}</td>
    </tr>
    @if(isset($item['children']) && !empty($item['children']))
        @include('inventory::productInventory.productInventory_pdf_detail', 
                [
                    'data' => $item['children'],
                    'local' => $local,
                    'level' => $level + 1
                ])
    @endif               
@endforeach