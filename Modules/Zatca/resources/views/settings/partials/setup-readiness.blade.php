@php
    $level = $readiness['level'] ?? 'empty';
    $percent = (int) ($readiness['percent'] ?? 0);
    $tone = match ($level) {
        'ready' => 'success',
        'almost' => 'warning',
        'partial' => 'info',
        default => 'danger',
    };
    $ringColor = match ($level) {
        'ready' => '#12b76a',
        'almost' => '#f79009',
        'partial' => '#0ba5ec',
        default => '#f04438',
    };
    // Default: collapse when fully ready; expand when there are gaps.
    $defaultExpanded = $level !== 'ready';
@endphp

<div class="z-readiness z-readiness--{{ $tone }} mb-4"
     id="zatca-readiness"
     data-default-expanded="{{ $defaultExpanded ? '1' : '0' }}">
    <button type="button"
            class="z-readiness__toggle"
            id="zatca-readiness-toggle"
            data-bs-toggle="collapse"
            data-bs-target="#zatca-readiness-body"
            aria-expanded="{{ $defaultExpanded ? 'true' : 'false' }}"
            aria-controls="zatca-readiness-body">
        <div class="z-readiness__toggle-main">
            <span class="z-readiness__mini-pct" style="--z-ready-color: {{ $ringColor }};">
                {{ $percent }}%
            </span>
            <div class="z-readiness__toggle-copy">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="z-readiness__title">{{ __('zatca::lang.readiness_title') }}</span>
                    <span class="badge badge-light-{{ $tone }}">{{ __('zatca::lang.readiness_level_'.$level) }}</span>
                </div>
                <span class="z-readiness__toggle-hint text-muted">
                    {{ __('zatca::lang.readiness_progress_count', [
                        'done' => $readiness['done_count'] ?? 0,
                        'total' => $readiness['total_count'] ?? 0,
                    ]) }}
                    ·
                    <span class="z-readiness__toggle-action" data-label-expand="{{ __('zatca::lang.readiness_expand') }}" data-label-collapse="{{ __('zatca::lang.readiness_collapse') }}">
                        {{ $defaultExpanded ? __('zatca::lang.readiness_collapse') : __('zatca::lang.readiness_expand') }}
                    </span>
                </span>
            </div>
        </div>
        <span class="z-readiness__chevron">
            <i class="fa fa-chevron-down"></i>
        </span>
    </button>

    <div id="zatca-readiness-body"
         class="collapse {{ $defaultExpanded ? 'show' : '' }}">
        <div class="z-readiness__hero">
            <div class="z-readiness__meter" style="--z-ready-color: {{ $ringColor }}; --z-ready-pct: {{ $percent }};">
                <svg viewBox="0 0 36 36" class="z-readiness__ring" aria-hidden="true">
                    <path class="z-readiness__ring-bg"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <path class="z-readiness__ring-fg"
                          stroke-dasharray="{{ $percent }}, 100"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                </svg>
                <div class="z-readiness__pct">
                    <span class="z-readiness__pct-num">{{ $percent }}%</span>
                    <span class="z-readiness__pct-label">{{ __('zatca::lang.readiness_complete') }}</span>
                </div>
            </div>

            <div class="z-readiness__copy">
                <p class="z-readiness__summary mb-3">{{ $readiness['summary'] }}</p>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="z-chip {{ ($readiness['can_generate'] ?? false) ? 'z-chip--ok' : 'z-chip--warn' }}">
                        <i class="fa {{ ($readiness['can_generate'] ?? false) ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                        {{ ($readiness['can_generate'] ?? false) ? __('zatca::lang.readiness_can_generate') : __('zatca::lang.readiness_cannot_generate') }}
                    </span>
                    <span class="z-chip {{ ($readiness['can_sync'] ?? false) ? 'z-chip--ok' : 'z-chip--warn' }}">
                        <i class="fa {{ ($readiness['can_sync'] ?? false) ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                        {{ ($readiness['can_sync'] ?? false) ? __('zatca::lang.readiness_can_sync') : __('zatca::lang.readiness_cannot_sync') }}
                    </span>
                </div>

                @if (! empty($readiness['missing']))
                    <div class="z-readiness__missing">
                        <div class="z-readiness__missing-title">
                            <i class="fa fa-list-ul me-1"></i>
                            {{ __('zatca::lang.readiness_missing_title') }}
                        </div>
                        <div class="z-readiness__missing-list">
                            @foreach ($readiness['missing'] as $gap)
                                <button type="button"
                                        class="z-gap-pill"
                                        data-zatca-focus="{{ $gap['anchor'] }}"
                                        title="{{ $gap['hint'] }}">
                                    <span class="z-gap-pill__label">{{ $gap['label'] }}</span>
                                    <span class="z-gap-pill__group">{{ $gap['group'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="z-readiness__all-good">
                        <i class="fa fa-shield-alt me-2"></i>
                        {{ __('zatca::lang.readiness_all_good') }}
                    </div>
                @endif
            </div>
        </div>

        <div class="z-readiness__groups">
            @foreach ($readiness['groups'] as $group)
                <div class="z-ready-group {{ ($group['complete'] ?? false) ? 'is-complete' : 'is-incomplete' }}">
                    <div class="z-ready-group__head">
                        <div class="d-flex align-items-center gap-2">
                            <span class="z-ready-group__icon"><i class="fa {{ $group['icon'] ?? 'fa-circle' }}"></i></span>
                            <div>
                                <div class="z-ready-group__title">{{ $group['label'] }}</div>
                                <div class="z-ready-group__meta">
                                    {{ $group['done'] }}/{{ $group['total'] }}
                                    — {{ $group['percent'] }}%
                                </div>
                            </div>
                        </div>
                        <span class="badge {{ ($group['complete'] ?? false) ? 'badge-light-success' : 'badge-light-warning' }}">
                            {{ ($group['complete'] ?? false) ? __('zatca::lang.readiness_group_ok') : __('zatca::lang.readiness_group_gap') }}
                        </span>
                    </div>
                    <ul class="z-ready-group__items">
                        @foreach ($group['items'] as $item)
                            <li class="{{ $item['ok'] ? 'is-ok' : 'is-miss' }}">
                                <button type="button"
                                        class="z-ready-item"
                                        data-zatca-focus="{{ $item['anchor'] }}">
                                    <span class="z-ready-item__state">
                                        <i class="fa {{ $item['ok'] ? 'fa-check' : 'fa-times' }}"></i>
                                    </span>
                                    <span class="z-ready-item__body">
                                        <span class="z-ready-item__label">{{ $item['label'] }}</span>
                                        @unless ($item['ok'])
                                            <span class="z-ready-item__hint">{{ $item['hint'] }}</span>
                                        @endunless
                                    </span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</div>
