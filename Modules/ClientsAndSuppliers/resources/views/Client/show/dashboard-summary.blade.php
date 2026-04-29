<div class="container mb-6">
    @php
        $invoiceScopeLabel = $isSupplier ? 'المشتريات' : 'المبيعات';
        $voucherScopeLabel = $isSupplier ? 'الصرف' : 'القبض';
        $typeLabel = function (string $type) use ($isSupplier) {
            return match ($type) {
                'sell' => 'مبيعات',
                'sell-return' => 'مردود مبيعات',
                'purchases' => 'مشتريات',
                'purchases-return' => 'مردود مشتريات',
                default => $type,
            };
        };
    @endphp
    <div class="row g-5">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                        <div>
                            <div class="text-muted fw-semibold">
                                {{ $isSupplier ? __('clientsandsuppliers::general.Supplier') : __('clientsandsuppliers::general.client') }}
                            </div>
                            <div class="fs-2 fw-bold">{{ $contact->name }}</div>
                            <div class="text-muted">
                                {{ $contact->tax_number ? __('clientsandsuppliers::fields.tax_number') . ': ' . $contact->tax_number : '' }}
                                {{ $contact->commercial_register ? ' · ' . __('clientsandsuppliers::fields.commercial_register') . ': ' . $contact->commercial_register : '' }}
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ $isSupplier ? route('create-suppliers-receipts') : route('create-receipts') }}"
                                class="btn btn-sm btn-primary">
                                {{ $isSupplier ? __('clientsandsuppliers::dashboard.add_payment') : __('clientsandsuppliers::dashboard.add_receipt') }}
                            </a>
                            <a href="{{ url('/client-edit/' . $contact->id) }}" class="btn btn-sm btn-light">
                                {{ __('messages.edit') }}
                            </a>
                        </div>
                    </div>

                    <div class="separator my-4"></div>

                    <div class="row g-4">
                        <div class="col-6 col-md-3">
                            <div class="text-muted fw-semibold mb-1">{{ __('clientsandsuppliers::dashboard.total') }}</div>
                            <div class="fs-4 fw-bold">@format_currency($totals->invoices_total ?? 0)</div>
                            <div class="text-muted fs-8">{{ ($totals->invoices_count ?? 0) }} {{ __('clientsandsuppliers::dashboard.invoices') }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted fw-semibold mb-1">{{ __('clientsandsuppliers::dashboard.paid') }}</div>
                            <div class="fs-4 fw-bold text-success">@format_currency($paidTotal)</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted fw-semibold mb-1">{{ __('clientsandsuppliers::dashboard.remaining') }}</div>
                            <div class="fs-4 fw-bold text-danger">@format_currency($outstandingTotal)</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted fw-semibold mb-1">{{ __('clientsandsuppliers::dashboard.open_invoices') }}</div>
                            <div class="fs-4 fw-bold">{{ (int) ($totals->open_invoices_count ?? 0) }}</div>
                            <div class="text-muted fs-8">{{ __('clientsandsuppliers::dashboard.invoices') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <div class="card-title fw-bold">{{ __('clientsandsuppliers::dashboard.ageing') }}</div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="text-muted">0 - 30</div>
                        <div class="fw-bold">@format_currency($ageing->b0_30 ?? 0)</div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="text-muted">31 - 60</div>
                        <div class="fw-bold">@format_currency($ageing->b31_60 ?? 0)</div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="text-muted">61 - 90</div>
                        <div class="fw-bold">@format_currency($ageing->b61_90 ?? 0)</div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="text-muted">90+</div>
                        <div class="fw-bold">@format_currency($ageing->b90_plus ?? 0)</div>
                    </div>
                    <div class="text-muted fs-8 mt-4">
                        {{ __('clientsandsuppliers::dashboard.ageing_note') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="row g-5">
                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <div class="card-title fw-bold">{{ __('clientsandsuppliers::dashboard.latest_invoices') }} ({{ $invoiceScopeLabel }})</div>
                            <div class="card-toolbar">
                                <a class="btn btn-sm btn-light" href="{{ $viewAllInvoicesUrl }}">{{ __('clientsandsuppliers::dashboard.view_all') }}</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted fw-semibold fs-8 text-uppercase">
                                            <th class="ps-4">#</th>
                                            <th>{{ __('clientsandsuppliers::dashboard.date') }}</th>
                                            <th>{{ __('clientsandsuppliers::dashboard.status') }}</th>
                                            <th class="text-end pe-4">{{ __('clientsandsuppliers::dashboard.amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recentInvoices as $inv)
                                            <tr>
                                                <td class="ps-4">
                                                    <a class="fw-bold text-hover-primary"
                                                        href="{{ url('/transaction-show/' . $inv->id) }}">{{ $inv->ref_no }}</a>
                                                    <div class="text-muted fs-8">{{ $typeLabel((string) $inv->type) }}</div>
                                                </td>
                                                <td>{{ $inv->transaction_date }}</td>
                                                <td>
                                                    <span
                                                        class="badge badge-light-{{ $inv->payment_status === 'paid' ? 'success' : ($inv->payment_status === 'partial' ? 'warning' : 'danger') }}">
                                                        {{ $inv->payment_status }}
                                                    </span>
                                                </td>
                                                <td class="text-end pe-4 fw-bold">@format_currency($inv->final_total)</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-6">
                                                    {{ __('clientsandsuppliers::dashboard.no_data') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <div class="card-title fw-bold">{{ __('clientsandsuppliers::dashboard.latest_payments') }} ({{ $voucherScopeLabel }})</div>
                            <div class="card-toolbar">
                                <a class="btn btn-sm btn-light" href="{{ $viewAllPaymentsUrl }}">{{ __('clientsandsuppliers::dashboard.view_all') }}</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted fw-semibold fs-8 text-uppercase">
                                            <th class="ps-4">#</th>
                                            <th>{{ __('clientsandsuppliers::dashboard.date') }}</th>
                                            <th>{{ __('clientsandsuppliers::dashboard.transaction') }}</th>
                                            <th>{{ __('clientsandsuppliers::dashboard.payment_method') }}</th>
                                            <th>{{ __('clientsandsuppliers::dashboard.account') }}</th>
                                            <th>{{ __('clientsandsuppliers::dashboard.notes') }}</th>
                                            <th class="text-end pe-4">{{ __('clientsandsuppliers::dashboard.amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recentPayments as $p)
                                            <tr>
                                                <td class="ps-4 fw-bold">{{ $p->payment_ref_no ?? $p->id }}</td>
                                                <td>{{ $p->paid_on ?? '--' }}</td>
                                                <td>
                                                    @if ($p->transaction)
                                                        <a class="fw-bold text-hover-primary"
                                                            href="{{ url('/transaction-show/' . $p->transaction->id) }}">{{ $p->transaction->ref_no }}</a>
                                                        <div class="text-muted fs-8">{{ $typeLabel((string) $p->transaction->type) }}</div>
                                                    @else
                                                        <span class="text-muted">--</span>
                                                    @endif
                                                </td>
                                                <td>{{ $p->method ?? '--' }}</td>
                                                <td>
                                                    @if ($p->account)
                                                        <div class="fw-semibold">{{ $p->account->gl_code ?? '' }}</div>
                                                        <div class="text-muted fs-8">{{ app()->getLocale() === 'ar' ? ($p->account->name_ar ?? '') : ($p->account->name_en ?? $p->account->name_ar ?? '') }}</div>
                                                    @else
                                                        <span class="text-muted">--</span>
                                                    @endif
                                                </td>
                                                <td class="text-muted">{{ $p->note ?? '--' }}</td>
                                                <td class="text-end pe-4 fw-bold">@format_currency($p->amount)</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-6">
                                                    {{ __('clientsandsuppliers::dashboard.no_data') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

