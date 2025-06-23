@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">سجل الجرد الدوري</h1>

    <a href="{{ route('periodic-inventory.create') }}" class="btn btn-primary mb-3">
        <i class="fas fa-plus"></i> جرد جديد
    </a>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>رقم الجرد</th>
                        <th>الفترة</th>
                        <th>قيمة أول المدة</th>
                        <th>قيمة المشتريات</th>
                        <th>قيمة آخر المدة</th>
                        <th>تكلفة المبيعات</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inventories as $inventory)
                    <tr>
                        <td>{{ $inventory->id }}</td>
                        <td>
                            {{ $inventory->start_date->format('Y-m-d') }}
                            إلى
                            {{ $inventory->end_date->format('Y-m-d') }}
                        </td>
                        <td>{{ number_format($inventory->opening_stock_value, 2) }}</td>
                        <td>{{ number_format($inventory->purchases_value, 2) }}</td>
                        <td>{{ number_format($inventory->closing_stock_value, 2) }}</td>
                        <td>{{ number_format($inventory->cogs, 2) }}</td>
                        <td>
                            <a href="{{ route('periodic-inventory.show', $inventory->id) }}"
                               class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
