@extends('layouts.app')

@section('content')
    @viteReactRefresh
    @vite('resources/components/App.jsx')
    @if(($inventoryPolicy ?? 'perpetual') === 'periodic')
        <div class="alert alert-warning d-flex align-items-start gap-3 mb-5">
            <i class="fas fa-info-circle mt-1"></i>
            <div>
                <div class="fw-bold">
                    {{ app()->getLocale() === 'ar' ? 'سياسة الجرد الحالية: جرد دوري' : 'Current Inventory Policy: Periodic' }}
                </div>
                <div>
                    {{ app()->getLocale() === 'ar'
                        ? 'المعروض هنا يعتمد على آخر جرد دوري مُرحّل لكل مستودع. آخر تاريخ جرد: '
                        : 'Displayed quantities are based on the latest posted periodic count per warehouse. Last count date: ' }}
                    <span class="fw-semibold">{{ $lastPeriodicSnapshot ?: (app()->getLocale() === 'ar' ? 'غير متوفر' : 'N/A') }}</span>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info d-flex align-items-start gap-3 mb-5">
            <i class="fas fa-sync-alt mt-1"></i>
            <div>
                <div class="fw-bold">
                    {{ app()->getLocale() === 'ar' ? 'سياسة الجرد الحالية: جرد مستمر' : 'Current Inventory Policy: Perpetual' }}
                </div>
                <div>
                    {{ app()->getLocale() === 'ar'
                        ? 'الكميات المعروضة لحظية ومباشرة من حركة المخزون الحالية.'
                        : 'Displayed quantities are live and directly reflect current inventory movements.' }}
                </div>
            </div>
        </div>
    @endif

      <div id="root" type="productinventory" 
	  list-url="{{json_encode(value: route('productInventoryList'))}}"
      summary-url="{{ json_encode(route('productInventorySummary')) }}"
      critical-csv-url="{{ json_encode(route('productInventoryCriticalCsv')) }}"
      ems-can="{{ \Modules\Inventory\Support\InventoryAccess::uiJson('product') }}"
	  dir = "{{ app()->getLocale() == 'en'? 'ltr' : 'rtl'}}"></div>

@endsection
