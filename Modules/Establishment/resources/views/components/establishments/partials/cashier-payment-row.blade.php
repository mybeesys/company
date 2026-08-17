@php
    $showAssignment = isset($branchOptions);
    $detailsTabId = 'cashier-pay-details-'.$index;
    $assignTabId = 'cashier-pay-assign-'.$index;
@endphp
<div class="border border-gray-300 rounded p-4 cashier-payment-row bg-body" data-cashier-row data-catalog-item>
    @if (! empty($row['id']))
        <input type="hidden" name="cashier_payment_rows[{{ $index }}][id]" value="{{ $row['id'] }}">
    @endif
    @if (! empty($row['payment_method_key']))
        <input type="hidden" name="cashier_payment_rows[{{ $index }}][payment_method_key]" value="{{ $row['payment_method_key'] }}">
    @endif
    @if ($showAssignment)
        @include('establishment::components.establishments.partials.catalog-item-tabs', [
            'detailsTabId' => $detailsTabId,
            'assignTabId' => $assignTabId,
            'row' => $row,
        ])
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
        </div>
    @endif
</div>
