@php
    $zatcaOps = $zatcaOps ?? [];
    $docType = $docType ?? 'invoice'; // invoice | return
    $isInstant = ! empty($zatcaOps) && ($zatcaOps['auto_sync_mode'] ?? '') === 'instant';
    $isReady = ! empty($zatcaOps['is_configured']);
@endphp

@if ($isInstant)
    <style>
        .zatca-live-badge {
            --zl-ok: #0f766e;
            --zl-warn: #b45309;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            max-width: 100%;
            padding: .28rem .7rem .28rem .4rem;
            border-radius: 999px;
            border: 1px solid rgba(15, 118, 110, .22);
            background: linear-gradient(135deg, #f0fdfa 0%, #ffffff 55%);
            color: var(--zl-ok);
            text-decoration: none !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
            transition: border-color .2s ease, box-shadow .2s ease, transform .15s ease;
            vertical-align: middle;
            line-height: 1.2;
            white-space: nowrap;
        }
        .zatca-live-badge:hover {
            border-color: rgba(15, 118, 110, .4);
            box-shadow: 0 4px 14px rgba(15, 118, 110, .12);
            transform: translateY(-1px);
            color: var(--zl-ok);
        }
        .zatca-live-badge.is-pending {
            border-color: rgba(180, 83, 9, .28);
            background: linear-gradient(135deg, #fffbeb 0%, #ffffff 55%);
            color: var(--zl-warn);
        }
        .zatca-live-badge.is-pending:hover {
            border-color: rgba(180, 83, 9, .45);
            box-shadow: 0 4px 14px rgba(180, 83, 9, .12);
            color: var(--zl-warn);
        }
        .zatca-live-badge__mark {
            position: relative;
            width: 1.55rem;
            height: 1.55rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 0;
            flex-shrink: 0;
        }
        .zatca-live-badge__mark-dot {
            width: .42rem;
            height: .42rem;
            border-radius: 50%;
            background: var(--zl-ok);
            box-shadow: 0 0 0 0 rgba(15, 118, 110, .45);
            animation: zatca-live-pulse 2s ease-out infinite;
        }
        .zatca-live-badge.is-pending .zatca-live-badge__mark-dot {
            background: var(--zl-warn);
            box-shadow: 0 0 0 0 rgba(180, 83, 9, .4);
            animation-name: zatca-live-pulse-warn;
        }
        .zatca-live-badge__text {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: .05rem;
            min-width: 0;
            padding-inline-end: .15rem;
        }
        .zatca-live-badge__title {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .01em;
        }
        .zatca-live-badge__sub {
            font-size: .62rem;
            font-weight: 500;
            opacity: .85;
            max-width: 11.5rem;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @keyframes zatca-live-pulse {
            0% { box-shadow: 0 0 0 0 rgba(15, 118, 110, .45); }
            70% { box-shadow: 0 0 0 8px rgba(15, 118, 110, 0); }
            100% { box-shadow: 0 0 0 0 rgba(15, 118, 110, 0); }
        }
        @keyframes zatca-live-pulse-warn {
            0% { box-shadow: 0 0 0 0 rgba(180, 83, 9, .4); }
            70% { box-shadow: 0 0 0 8px rgba(180, 83, 9, 0); }
            100% { box-shadow: 0 0 0 0 rgba(180, 83, 9, 0); }
        }
        @media (max-width: 575.98px) {
            .zatca-live-badge__sub { display: none; }
        }
        @media (prefers-reduced-motion: reduce) {
            .zatca-live-badge__mark-dot { animation: none; }
            .zatca-live-badge:hover { transform: none; }
        }
    </style>

    <a href="{{ route('zatca.settings.edit', ['tab' => 'operations']) }}"
       class="zatca-live-badge {{ $isReady ? '' : 'is-pending' }}"
       title="{{ $isReady
            ? ($docType === 'return'
                ? __('zatca::lang.sell_instant_badge_tip_return')
                : __('zatca::lang.sell_instant_badge_tip_invoice'))
            : __('zatca::lang.sell_instant_badge_tip_pending') }}"
       data-bs-toggle="tooltip"
       data-bs-placement="bottom">
        <span class="zatca-live-badge__mark" aria-hidden="true">
            <span class="zatca-live-badge__mark-dot"></span>
        </span>
        <span class="zatca-live-badge__text">
            <span class="zatca-live-badge__title">
                {{ $isReady
                    ? __('zatca::lang.sell_instant_badge_title')
                    : __('zatca::lang.sell_instant_badge_title_pending') }}
            </span>
            <span class="zatca-live-badge__sub">
                {{ $docType === 'return'
                    ? __('zatca::lang.sell_instant_badge_sub_return')
                    : __('zatca::lang.sell_instant_badge_sub_invoice') }}
            </span>
        </span>
    </a>
@endif
