<div class="z-card">
    <div class="z-card-header d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h2 class="z-card-title">{{ __('zatca::lang.tab_send_returns') }}</h2>
            <p class="z-card-subtitle mb-0">{{ __('zatca::lang.send_returns_subtitle') }}</p>
        </div>
        @if ($setting->isConfigured())
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('zatca.settings.edit', ['tab' => 'returns', 'zatca_return_status' => 'all']) }}"
                   class="badge {{ ($returnStatusFilter ?? 'all') === 'all' ? 'badge-primary' : 'badge-light' }} fs-7 px-3 py-2 text-decoration-none">
                    {{ __('zatca::lang.filter_all') }} ({{ $returnStatusCounts['all'] ?? 0 }})
                </a>
                <a href="{{ route('zatca.settings.edit', ['tab' => 'returns', 'zatca_return_status' => 'pending']) }}"
                   class="badge {{ ($returnStatusFilter ?? '') === 'pending' ? 'badge-warning' : 'badge-light-warning' }} fs-7 px-3 py-2 text-decoration-none">
                    {{ __('zatca::lang.sync_status_pending') }} ({{ $returnStatusCounts['pending'] ?? 0 }})
                </a>
                <a href="{{ route('zatca.settings.edit', ['tab' => 'returns', 'zatca_return_status' => 'synced']) }}"
                   class="badge {{ ($returnStatusFilter ?? '') === 'synced' ? 'badge-success' : 'badge-light-success' }} fs-7 px-3 py-2 text-decoration-none">
                    {{ __('zatca::lang.sync_status_synced') }} ({{ $returnStatusCounts['synced'] ?? 0 }})
                </a>
                <a href="{{ route('zatca.settings.edit', ['tab' => 'returns', 'zatca_return_status' => 'failed']) }}"
                   class="badge {{ ($returnStatusFilter ?? '') === 'failed' ? 'badge-danger' : 'badge-light-danger' }} fs-7 px-3 py-2 text-decoration-none">
                    {{ __('zatca::lang.sync_status_failed') }} ({{ $returnStatusCounts['failed'] ?? 0 }})
                </a>
            </div>
        @endif
    </div>
    <div class="z-card-body">
        @if (! $setting->isConfigured())
            <div class="alert alert-warning mb-0">
                {{ __('zatca::lang.send_requires_credentials') }}
            </div>
        @elseif ($sellReturns->total() === 0)
            <div class="alert alert-secondary mb-0">{{ __('zatca::lang.no_sell_returns') }}</div>
        @else
            <form method="POST" action="{{ route('zatca.settings.sync-sell') }}" id="zatca-sync-return-form">
                @csrf
                <input type="hidden" name="active_tab" value="returns">
                <input type="hidden" name="default_report_type" value="B2C">

                <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4 p-3 rounded border bg-light">
                    <div class="text-muted small pb-2">
                        <span id="zatca-return-selected-count">0</span> {{ __('zatca::lang.selected_invoices') }}
                    </div>
                    <button type="submit" class="btn btn-primary" id="zatca-bulk-return-sync-btn" disabled>
                        <i class="fa fa-sync-alt me-1"></i>
                        {{ __('zatca::lang.sync_selected_returns') }}
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3" id="zatca-return-sync-table">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th class="w-40px">
                                    <div class="form-check form-check-sm form-check-custom">
                                        <input class="form-check-input" type="checkbox" id="zatca-return-select-all">
                                    </div>
                                </th>
                                <th>{{ __('zatca::lang.col_ref') }}</th>
                                <th>{{ __('zatca::lang.col_parent_invoice') }}</th>
                                <th>{{ __('zatca::lang.col_client') }}</th>
                                <th>{{ __('zatca::lang.col_date') }}</th>
                                <th class="text-end">{{ __('zatca::lang.col_total') }}</th>
                                <th>{{ __('zatca::lang.col_sync_status') }}</th>
                                <th class="text-end">{{ __('zatca::lang.col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sellReturns as $return)
                                @php
                                    $sync = $returnSyncMap->get($return->id);
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
                                    $parentRef = optional($return->parentSell)->ref_no ?? ('#'.$return->parent_id);
                                    $rowType = $sync?->report_type
                                        ?: optional($parentSyncMap->get($return->parent_id))->report_type
                                        ?: 'B2C';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="form-check form-check-sm form-check-custom">
                                            <input class="form-check-input zatca-return-row-check"
                                                   type="checkbox"
                                                   name="transaction_ids[]"
                                                   value="{{ $return->id }}"
                                                   @disabled($status === 'synced')>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-gray-800">{{ $return->ref_no }}</span>
                                        <div class="text-muted small">{{ __('zatca::lang.doc_credit_note') }}</div>
                                    </td>
                                    <td>{{ $parentRef }}</td>
                                    <td>{{ optional($return->client)->name ?? __('zatca::lang.walk_in_customer') }}</td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($return->transaction_date)->format('Y-m-d') }}</td>
                                    <td class="text-end">{{ number_format((float) $return->final_total, 2) }}</td>
                                    <td>
                                        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                        @if ($sync?->reporting_status)
                                            @php
                                                $statusKey = 'zatca::lang.reporting_status_'.strtolower((string) $sync->reporting_status);
                                                $rsLabel = __($statusKey);
                                                if ($rsLabel === $statusKey) {
                                                    $rsLabel = $sync->reporting_status;
                                                }
                                            @endphp
                                            <div class="text-muted small mt-1">{{ $rsLabel }}</div>
                                        @endif
                                        @if ($sync?->last_error)
                                            <div class="z-sync-row-error mt-1" title="{{ $sync->last_error }}">
                                                {{ \Illuminate\Support\Str::limit($sync->last_error, 100) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <input type="hidden" name="report_types[{{ $return->id }}]" value="{{ $rowType }}">
                                        @if ($status === 'synced')
                                            <div class="btn-group">
                                                <a href="{{ route('zatca.documents.pdf', $return->id) }}"
                                                   class="btn btn-sm btn-light-danger"
                                                   title="{{ __('zatca::lang.download_pdf') }}">
                                                    <i class="fa fa-file-pdf"></i>
                                                </a>
                                                <a href="{{ route('zatca.documents.xml', $return->id) }}"
                                                   class="btn btn-sm btn-light-primary"
                                                   title="{{ __('zatca::lang.download_xml') }}">
                                                    <i class="fa fa-file-code"></i>
                                                </a>
                                                <a href="{{ route('zatca.documents.qr', $return->id) }}"
                                                   class="btn btn-sm btn-light-dark"
                                                   title="{{ __('zatca::lang.download_qr') }}">
                                                    <i class="fa fa-qrcode"></i>
                                                </a>
                                            </div>
                                        @else
                                            <button type="button"
                                                    class="btn btn-sm btn-light-primary zatca-sync-return-one-btn"
                                                    data-transaction-id="{{ $return->id }}"
                                                    title="{{ __('zatca::lang.sync_one') }}">
                                                <i class="fa fa-cloud-upload-alt"></i>
                                                {{ __('zatca::lang.sync_one') }}
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
                    <div class="text-muted small">
                        {{ __('zatca::lang.pagination_showing', [
                            'from' => $sellReturns->firstItem() ?? 0,
                            'to' => $sellReturns->lastItem() ?? 0,
                            'total' => $sellReturns->total(),
                        ]) }}
                    </div>
                    {{ $sellReturns->onEachSide(1)->links('zatca::pagination.bootstrap') }}
                </div>

                <div class="z-help mt-3">{{ __('zatca::lang.sync_returns_help') }}</div>
            </form>
        @endif
    </div>
</div>
