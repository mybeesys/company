@if (session('sync_feedback'))
    @php $fb = session('sync_feedback'); @endphp
    <div class="z-sync-feedback {{ ($fb['failed'] ?? 0) > 0 ? 'z-sync-feedback--error' : 'z-sync-feedback--ok' }} mb-4">
        <div class="z-sync-feedback__head">
            <div>
                <div class="z-sync-feedback__title">
                    {{ __('zatca::lang.sync_feedback_title') }}
                </div>
                <div class="z-sync-feedback__summary">
                    {{ __('zatca::lang.sync_batch_summary', [
                        'success' => $fb['success'] ?? 0,
                        'failed' => $fb['failed'] ?? 0,
                    ]) }}
                </div>
            </div>
        </div>
        @foreach (($fb['items'] ?? []) as $item)
            <div class="z-sync-item {{ ($item['ok'] ?? false) ? 'is-ok' : 'is-fail' }}">
                <div class="z-sync-item__top">
                    <span class="fw-bold">{{ $item['ref'] ?? '—' }}</span>
                    <span class="badge {{ ($item['ok'] ?? false) ? 'badge-light-success' : 'badge-light-danger' }}">
                        {{ ($item['ok'] ?? false) ? __('zatca::lang.sync_status_synced') : __('zatca::lang.sync_status_failed') }}
                    </span>
                    @if (! empty($item['reporting_status']))
                        @php
                            $statusLabel = $item['reporting_status_label'] ?? null;
                            if (! $statusLabel) {
                                $statusKey = 'zatca::lang.reporting_status_'.strtolower((string) $item['reporting_status']);
                                $translated = __($statusKey);
                                $statusLabel = $translated !== $statusKey
                                    ? $translated
                                    : $item['reporting_status'];
                            }
                        @endphp
                        <span class="badge badge-light">{{ $statusLabel }}</span>
                    @endif
                </div>
                <div class="z-sync-item__summary">{{ $item['summary'] ?? '' }}</div>
                @if (! empty($item['errors']))
                    <ul class="z-sync-item__list z-sync-item__list--errors">
                        @foreach ($item['errors'] as $err)
                            @php
                                $errCode = strtoupper((string) ($err['code'] ?? ''));
                                $showCode = $errCode !== ''
                                    && ! in_array($errCode, ['EXCEPTION', 'ERROR', 'CONNECTION', 'AUTH', 'ZATCA'], true);
                            @endphp
                            <li>
                                @if ($showCode)
                                    <code>{{ $err['code'] }}</code>
                                @endif
                                <span>{{ $err['message'] ?? '' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @if (! empty($item['warnings']))
                    <details class="z-sync-warnings">
                        <summary>{{ __('zatca::lang.sync_warnings_count', ['count' => count($item['warnings'])]) }}</summary>
                        <ul class="z-sync-item__list z-sync-item__list--warnings">
                            @foreach ($item['warnings'] as $warn)
                                <li>
                                    @if (! empty($warn['code']))
                                        <code>{{ $warn['code'] }}</code>
                                    @endif
                                    <span>{{ $warn['message'] ?? '' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>
        @endforeach
    </div>
@else
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
@endif
