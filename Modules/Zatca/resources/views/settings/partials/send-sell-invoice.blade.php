<div class="z-card">
    <div class="z-card-header d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h2 class="z-card-title">{{ __('zatca::lang.tab_send_sell') }}</h2>
            <p class="z-card-subtitle mb-0">{{ __('zatca::lang.send_sell_subtitle') }}</p>
        </div>
        @if ($setting->isConfigured())
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('zatca.settings.edit', ['tab' => 'send', 'zatca_status' => 'all']) }}"
                   class="badge {{ $statusFilter === 'all' ? 'badge-primary' : 'badge-light' }} fs-7 px-3 py-2 text-decoration-none">
                    {{ __('zatca::lang.filter_all') }} ({{ $statusCounts['all'] }})
                </a>
                <a href="{{ route('zatca.settings.edit', ['tab' => 'send', 'zatca_status' => 'pending']) }}"
                   class="badge {{ $statusFilter === 'pending' ? 'badge-warning' : 'badge-light-warning' }} fs-7 px-3 py-2 text-decoration-none">
                    {{ __('zatca::lang.sync_status_pending') }} ({{ $statusCounts['pending'] }})
                </a>
                <a href="{{ route('zatca.settings.edit', ['tab' => 'send', 'zatca_status' => 'synced']) }}"
                   class="badge {{ $statusFilter === 'synced' ? 'badge-success' : 'badge-light-success' }} fs-7 px-3 py-2 text-decoration-none">
                    {{ __('zatca::lang.sync_status_synced') }} ({{ $statusCounts['synced'] }})
                </a>
                <a href="{{ route('zatca.settings.edit', ['tab' => 'send', 'zatca_status' => 'failed']) }}"
                   class="badge {{ $statusFilter === 'failed' ? 'badge-danger' : 'badge-light-danger' }} fs-7 px-3 py-2 text-decoration-none">
                    {{ __('zatca::lang.sync_status_failed') }} ({{ $statusCounts['failed'] }})
                </a>
            </div>
        @endif
    </div>
    <div class="z-card-body">
        @if (! $setting->isConfigured())
            <div class="alert alert-warning mb-0">
                {{ __('zatca::lang.send_requires_credentials') }}
            </div>
        @elseif ($sellInvoices->isEmpty())
            <div class="alert alert-secondary mb-0">{{ __('zatca::lang.no_sell_invoices') }}</div>
        @else
            <form method="POST" action="{{ route('zatca.settings.sync-sell') }}" id="zatca-sync-sell-form">
                @csrf
                <input type="hidden" name="active_tab" value="send">

                <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4 p-3 rounded border bg-light">
                    <div class="d-flex flex-wrap align-items-end gap-3">
                        <div>
                            <label class="form-label mb-1" for="default_report_type">{{ __('zatca::lang.default_report_type') }}</label>
                            <select name="default_report_type" id="default_report_type" class="form-select form-select-solid form-select-sm" style="min-width: 120px;">
                                <option value="B2C" @selected(old('default_report_type', 'B2C') === 'B2C')>B2C</option>
                                <option value="B2B" @selected(old('default_report_type') === 'B2B')>B2B</option>
                            </select>
                        </div>
                        <div class="text-muted small pb-2">
                            <span id="zatca-selected-count">0</span> {{ __('zatca::lang.selected_invoices') }}
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" id="zatca-bulk-sync-btn" disabled>
                        <i class="fa fa-sync-alt me-1"></i>
                        {{ __('zatca::lang.sync_selected') }}
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3" id="zatca-sell-sync-table">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th class="w-40px">
                                    <div class="form-check form-check-sm form-check-custom">
                                        <input class="form-check-input" type="checkbox" id="zatca-select-all">
                                    </div>
                                </th>
                                <th>{{ __('zatca::lang.col_ref') }}</th>
                                <th>{{ __('zatca::lang.col_client') }}</th>
                                <th>{{ __('zatca::lang.col_date') }}</th>
                                <th class="text-end">{{ __('zatca::lang.col_total') }}</th>
                                <th>{{ __('zatca::lang.report_type') }}</th>
                                <th>{{ __('zatca::lang.col_sync_status') }}</th>
                                <th>{{ __('zatca::lang.col_last_attempt') }}</th>
                                <th class="text-end">{{ __('zatca::lang.col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sellInvoices as $invoice)
                                @php
                                    $sync = $syncMap->get($invoice->id);
                                    $status = $sync?->status ?? 'pending';
                                    $statusLabel = match ($status) {
                                        'synced' => __('zatca::lang.sync_status_synced'),
                                        'failed' => __('zatca::lang.sync_status_failed'),
                                        default => __('zatca::lang.sync_status_pending'),
                                    };
                                    $badgeClass = match ($status) {
                                        'synced' => 'badge-light-success',
                                        'failed' => 'badge-light-danger',
                                        default => 'badge-light-warning',
                                    };
                                    $rowType = old('report_types.'.$invoice->id, $sync?->report_type ?: 'B2C');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="form-check form-check-sm form-check-custom">
                                            <input class="form-check-input zatca-row-check"
                                                   type="checkbox"
                                                   name="transaction_ids[]"
                                                   value="{{ $invoice->id }}">
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-gray-800">{{ $invoice->ref_no }}</span>
                                    </td>
                                    <td>{{ optional($invoice->client)->name ?? __('zatca::lang.walk_in_customer') }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($invoice->transaction_date)->format('Y-m-d') }}</td>
                                    <td class="text-end">{{ number_format((float) $invoice->final_total, 2) }}</td>
                                    <td>
                                        <select name="report_types[{{ $invoice->id }}]"
                                                class="form-select form-select-solid form-select-sm zatca-row-type"
                                                style="min-width: 100px;">
                                            <option value="B2C" @selected($rowType === 'B2C')>B2C</option>
                                            <option value="B2B" @selected($rowType === 'B2B')>B2B</option>
                                        </select>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                        @if ($sync?->last_error)
                                            <div class="text-danger small mt-1" style="max-width: 220px;"
                                                 title="{{ $sync->last_error }}">
                                                {{ \Illuminate\Support\Str::limit($sync->last_error, 80) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-muted small">
                                        {{ $sync?->last_attempt_at?->format('Y-m-d H:i') ?? __('zatca::lang.never') }}
                                    </td>
                                    <td class="text-end">
                                        <button type="button"
                                                class="btn btn-sm btn-light-primary zatca-sync-one-btn"
                                                data-transaction-id="{{ $invoice->id }}"
                                                title="{{ __('zatca::lang.sync_one') }}">
                                            <i class="fa fa-cloud-upload-alt"></i>
                                            {{ __('zatca::lang.sync_one') }}
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="z-help mt-3">{{ __('zatca::lang.sync_table_help') }}</div>
            </form>
        @endif
    </div>
</div>
