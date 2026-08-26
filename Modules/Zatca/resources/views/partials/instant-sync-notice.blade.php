@php
    $zatcaOps = $zatcaOps ?? [];
    $docType = $docType ?? 'invoice'; // invoice | return
    $dismissKey = 'zatca_instant_notice_dismissed_'.$docType;
@endphp

@if (! empty($zatcaOps) && ($zatcaOps['auto_sync_mode'] ?? '') === 'instant')
    <div class="container mb-3" id="zatca-instant-sync-notice" data-dismiss-key="{{ $dismissKey }}" hidden>
        <div class="d-flex flex-wrap align-items-center gap-2 py-2 px-3 rounded-2 border border-dashed"
             style="border-color:#d8dde6 !important; background:#f8f9fb;">
            <i class="ki-duotone ki-information-2 fs-3 text-gray-600 flex-shrink-0">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <div class="flex-grow-1 fs-7 text-gray-700 min-w-200px">
                @if ($docType === 'return')
                    {{ __('zatca::lang.sell_instant_sync_notice_return') }}
                @else
                    {{ __('zatca::lang.sell_instant_sync_notice') }}
                @endif
                <span class="text-gray-600">{{ __('zatca::lang.sell_instant_sync_not_draft') }}</span>
                @if (empty($zatcaOps['is_configured']))
                    <span class="d-block mt-1 text-danger fs-8">{{ __('zatca::lang.sell_instant_sync_not_configured') }}</span>
                @endif
            </div>
            <a href="{{ route('zatca.settings.edit', ['tab' => 'operations']) }}"
               class="btn btn-sm btn-light btn-active-light-primary flex-shrink-0 py-1 px-3">
                {{ __('zatca::lang.sell_instant_sync_settings_btn') }}
            </a>
            <button type="button"
                    class="btn btn-sm btn-icon btn-active-light flex-shrink-0"
                    id="zatca-instant-sync-dismiss"
                    aria-label="{{ __('zatca::lang.sell_instant_sync_dismiss') }}">
                <i class="ki-duotone ki-cross fs-3"><span class="path1"></span><span class="path2"></span></i>
            </button>
        </div>
    </div>
    <script>
        (function () {
            var box = document.getElementById('zatca-instant-sync-notice');
            if (!box) return;
            var key = box.getAttribute('data-dismiss-key') || 'zatca_instant_notice_dismissed';
            try {
                if (sessionStorage.getItem(key) === '1') {
                    box.remove();
                    return;
                }
            } catch (e) {}
            box.hidden = false;
            var btn = document.getElementById('zatca-instant-sync-dismiss');
            if (btn) {
                btn.addEventListener('click', function () {
                    try { sessionStorage.setItem(key, '1'); } catch (e) {}
                    box.remove();
                });
            }
        })();
    </script>
@endif
