@extends('layouts.app')

@section('content')
<div class="d-flex flex-column flex-xl-row">
    <div class="flex-column flex-lg-row-auto w-100 w-xl-350px mb-10">
        <div class="card mb-5 mb-xl-8">
            <div class="card-body pt-15">
                <div class="d-flex flex-center flex-column mb-5">
                    <div class="symbol symbol-100px symbol-circle mb-7">
                        <span class="symbol-label fs-2x fw-bold text-primary">{{ mb_substr($company->name_ar, 0, 1) }}</span>
                    </div>
                    <a href="#" class="fs-3 text-gray-800 text-hover-primary fw-bold mb-1">{{ $company->name_ar }}</a>
                    <span class="fs-5 fw-semibold text-muted mb-6">{{ $company->name_en }}</span>
                </div>

                <div class="separator separator-dashed my-3"></div>
                
                <div class="pb-5 fs-6">
                    <div class="fw-bold mt-5">الرقم الضريبي</div>
                    <div class="text-gray-600">{{ $company->vat_no }}</div>
                    
                    <div class="fw-bold mt-5">العنوان</div>
                    <div class="text-gray-600">{{ $company->city }}, {{ $company->street }}</div>
                    
                    <div class="fw-bold mt-5">الحساب المحاسبي</div>
                    <div class="badge badge-light-info">{{ $company->account }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex-lg-row-fluid ms-lg-15">
        <div class="card card-flush mb-6 mb-xl-9">
            <div class="card-header mt-6">
                <div class="card-title flex-column">
                    <h2 class="mb-1">سجل عقود الفرنشايز</h2>
                    <div class="fs-6 fw-semibold text-muted">إجمالي العقود: {{ $company->contracts->count() }}</div>
                </div>
                <div class="card-toolbar">
                    <button class="btn btn-sm btn-light-primary">إضافة عقد جديد</button>
                </div>
            </div>
            <div class="card-body p-9 pt-4">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>مدة العقد</th>
                            <th>تاريخ البداية</th>
                            <th>تاريخ النهاية</th>
                            <th>الرسوم</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600">
                        @foreach($company->contracts as $contract)
                        <tr>
                            <td>{{ $contract->contract_duration }} شهر</td>
                            <td>{{ $contract->start_date }}</td>
                            <td>{{ $contract->end_date }}</td>
                            <td>{{ number_format($contract->reality_fees, 2) }} ر.س</td>
                            <td>
                                @if($contract->end_date < now())
                                    <span class="badge badge-light-danger">منتهي</span>
                                @else
                                    <span class="badge badge-light-success">فعال</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection