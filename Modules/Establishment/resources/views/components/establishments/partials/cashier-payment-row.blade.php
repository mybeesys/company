@php
    $showAssignment = isset($branchOptions);
    $detailsTabId = 'cashier-pay-details-'.$index;
    $assignTabId  = 'cashier-pay-assign-'.$index;
    $feesTabId    = 'cashier-pay-fees-'.$index;
    $feesCount    = count($row['fees'] ?? []);
    $locale       = $locale ?? app()->getLocale();
@endphp
<div class="border border-gray-300 rounded p-4 cashier-payment-row bg-body" data-cashier-row data-catalog-item>
    @if (! empty($row['id']))
        <input type="hidden" name="cashier_payment_rows[{{ $index }}][id]" value="{{ $row['id'] }}">
    @endif
    @if (! empty($row['payment_method_key']))
        <input type="hidden" name="cashier_payment_rows[{{ $index }}][payment_method_key]" value="{{ $row['payment_method_key'] }}">
    @endif
    @if ($showAssignment)
        {{-- تبويبات: التفاصيل | الفروع | الرسوم --}}
        @php $assignedCount = count(array_filter(array_map('intval', $row['establishment_ids'] ?? []))); @endphp
        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x catalog-item-tabs mb-5" role="tablist">
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab"
                   href="#{{ $detailsTabId }}" role="tab">
                    @lang('establishment::general.settings_details_tab')
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4 d-inline-flex align-items-center gap-2"
                   data-bs-toggle="tab" href="#{{ $assignTabId }}" role="tab">
                    @lang('establishment::general.assign_to_branches_tab')
                    <span class="badge badge-light-primary fw-bold rounded-pill px-3 py-2 branch-assign-tab-count">{{ $assignedCount }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4 d-inline-flex align-items-center gap-2"
                   data-bs-toggle="tab" href="#{{ $feesTabId }}" role="tab">
                    @lang('establishment::fields.payment_method_fees_tab')
                    <span class="badge badge-light-warning fw-bold rounded-pill px-3 py-2 pmf-fees-tab-count">{{ $feesCount }}</span>
                </a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="{{ $detailsTabId }}">
    @endif
    <div class="row g-4 align-items-end">
        <div class="col-md-5">
            <label class="form-label fw-semibold mb-2">@lang('establishment::fields.name')</label>
            <input type="text" class="form-control form-control-solid"
                name="cashier_payment_rows[{{ $index }}][name_ar]"
                value="{{ $row['name_ar'] ?? '' }}"
                placeholder="@lang('establishment::fields.name')">
        </div>
        <div class="col-md-5">
            <label class="form-label fw-semibold mb-2">@lang('establishment::fields.name_en')</label>
            <input type="text" class="form-control form-control-solid"
                name="cashier_payment_rows[{{ $index }}][name_en]"
                value="{{ $row['name_en'] ?? '' }}"
                placeholder="@lang('establishment::fields.name_en')">
        </div>
        <div class="col-md-2 d-flex gap-2 pb-1">
            <button type="button" class="btn btn-sm btn-light-danger cashier-remove-row"
                title="@lang('messages.delete')">
                <i class="ki-outline ki-trash fs-4"></i>
            </button>
        </div>
    </div>
    @if ($showAssignment)
                <p class="text-muted fs-7 mt-3 mb-0">@lang('establishment::general.cashier_payment_details_hint')</p>
            </div>
            <div class="tab-pane fade" id="{{ $assignTabId }}">
                @include('establishment::components.establishments.partials.branch-assignment-with-accounts', [
                    'index' => $index,
                    'row' => $row,
                    'namePrefix' => 'cashier_payment_rows',
                    'branchOptions' => $branchOptions,
                    'accounts' => $accounts ?? [],
                    'locale' => $locale,
                ])
            </div>
            <div class="tab-pane fade" id="{{ $feesTabId }}">
                @include('establishment::components.establishments.partials.payment-method-fees-tab', [
                    'index' => $index,
                    'row' => $row,
                    'locale' => $locale,
                ])
            </div>
        </div>
    @endif
</div>
