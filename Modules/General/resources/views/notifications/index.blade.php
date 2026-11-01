@extends('layouts.app')
@section('title', __('general.notifications'))

@section('css')
<style>
    .nc-page { max-width: 980px; margin: 0 auto; }
    .nc-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .85rem 1rem;
        margin-bottom: 1.15rem;
    }
    .nc-title {
        margin: 0;
        font-size: 1.35rem;
        font-weight: 800;
        color: #1e2129;
        letter-spacing: -.02em;
    }
    .nc-meta {
        margin: .2rem 0 0;
        font-size: .8rem;
        color: #7e8299;
    }
    .nc-modules {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
        margin-bottom: 1rem;
    }
    .nc-chip {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border: 1px solid #e4e6ef;
        background: #fff;
        color: #5e6278;
        border-radius: 8px;
        padding: .42rem .8rem;
        font-size: .78rem;
        font-weight: 650;
        text-decoration: none !important;
        transition: .15s ease;
        line-height: 1.2;
    }
    .nc-chip:hover { border-color: rgba(233,183,31,.55); color: #1e2129; background: #fffdf6; }
    .nc-chip.is-active {
        background: var(--bs-primary);
        border-color: var(--bs-primary);
        color: #1e2129;
        box-shadow: 0 1px 0 rgba(30,33,41,.04);
    }
    .nc-chip .nc-chip-count {
        min-width: 1.2rem;
        height: 1.15rem;
        border-radius: 6px;
        background: #f1f1f4;
        color: #5e6278;
        font-size: .68rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 .3rem;
        font-weight: 700;
    }
    .nc-chip.is-active .nc-chip-count { background: rgba(30,33,41,.12); color: #1e2129; }
    .nc-panel {
        border: 1px solid #eff2f5;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 6px 20px rgba(30,33,41,.04);
        overflow: hidden;
    }
    .nc-row {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: .9rem;
        align-items: start;
        padding: 1rem 1.15rem;
        border-bottom: 1px solid #f1f3f7;
        text-decoration: none !important;
        color: inherit;
        transition: background .12s ease;
        cursor: pointer;
    }
    .nc-row:last-child { border-bottom: 0; }
    .nc-row:hover { background: #fcfcfd; }
    .nc-row.is-unread { background: linear-gradient(90deg, rgba(233,183,31,.08), transparent 42%); }
    [dir="rtl"] .nc-row.is-unread { background: linear-gradient(270deg, rgba(233,183,31,.08), transparent 42%); }
    .nc-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bs-primary-light, #fdf6e3);
        color: #b88816;
        flex-shrink: 0;
    }
    .nc-icon.sev-warning { background: #fff8dd; color: #c9a227; }
    .nc-icon.sev-critical { background: #fff5f8; color: #f1416c; }
    .nc-body-title {
        font-size: .95rem;
        font-weight: 750;
        color: #1e2129;
        margin: 0 0 .25rem;
        line-height: 1.35;
    }
    .nc-body-text {
        margin: 0;
        font-size: .82rem;
        color: #7e8299;
        line-height: 1.55;
    }
    .nc-tags {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
        margin-top: .55rem;
        align-items: center;
    }
    .nc-tag {
        font-size: .68rem;
        font-weight: 700;
        border-radius: 6px;
        padding: .18rem .45rem;
        background: #f4f6fa;
        color: #5e6278;
    }
    .nc-tag.sev-info { background: #fdf6e3; color: #946f11; }
    .nc-tag.sev-warning { background: #fff8dd; color: #a98912; }
    .nc-tag.sev-critical { background: #fff5f8; color: #f1416c; }
    .nc-side {
        text-align: end;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: .45rem;
        min-width: 88px;
    }
    .nc-time { font-size: .72rem; color: #a1a5b7; white-space: nowrap; }
    .nc-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--bs-primary);
        display: inline-block;
    }
    .nc-actions { display: flex; gap: .25rem; opacity: .55; }
    .nc-row:hover .nc-actions { opacity: 1; }
    .nc-empty {
        text-align: center;
        padding: 3.5rem 1.5rem;
        color: #a1a5b7;
    }
    .nc-empty i { font-size: 2rem; color: #e9b71f; opacity: .7; }
    .nc-filters-inline {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        align-items: center;
    }
</style>
@endsection

@section('content')
@php
    $activeModule = request('module');
    $unreadOnly = request()->boolean('unread');
    $totalUnread = $unreadTotal ?? 0;
@endphp
<div class="container-fluid py-4">
    <div class="nc-page">
        <div class="nc-toolbar">
            <div>
                <h1 class="nc-title">@lang('general.notifications')</h1>
                <p class="nc-meta">
                    {{ $notifications->total() }} @lang('general::notifications.items_count')
                    @if ($totalUnread > 0)
                        · <span class="text-dark fw-semibold">{{ $totalUnread }}</span> @lang('general::notifications.unread_short')
                    @endif
                </p>
            </div>
            <div class="nc-filters-inline">
                <a href="{{ route('notifications.index', array_filter(['module' => $activeModule, 'unread' => $unreadOnly ? null : 1])) }}"
                   class="nc-chip {{ $unreadOnly ? 'is-active' : '' }}">
                    @lang('general::notifications.unread_only')
                </a>
                <button type="button" class="btn btn-sm btn-light-primary" id="notif-center-mark-all">
                    @lang('general::notifications.mark_all_read')
                </button>
            </div>
        </div>

        <div class="nc-modules" role="tablist">
            <a href="{{ route('notifications.index', array_filter(['unread' => $unreadOnly ? 1 : null])) }}"
               class="nc-chip {{ $activeModule === null || $activeModule === '' ? 'is-active' : '' }}">
                @lang('general::notifications.all_modules')
                <span class="nc-chip-count">{{ $moduleCounts['__all'] ?? $notifications->total() }}</span>
            </a>
            @foreach ($modules as $module)
                <a href="{{ route('notifications.index', array_filter(['module' => $module, 'unread' => $unreadOnly ? 1 : null])) }}"
                   class="nc-chip {{ $activeModule === $module ? 'is-active' : '' }}">
                    @lang('general::notifications.module_'.$module)
                    <span class="nc-chip-count">{{ $moduleCounts[$module] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        <div class="nc-panel">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data ?? [];
                    $sev = $data['severity'] ?? 'info';
                    $url = $data['url'] ?? null;
                    $module = $data['module'] ?? 'system';
                @endphp
                <div class="nc-row {{ $notification->read_at ? '' : 'is-unread' }} notif-open-row"
                     data-id="{{ $notification->id }}"
                     data-url="{{ $url }}">
                    <div class="nc-icon sev-{{ $sev }}">
                        <i class="ki-outline {{ $data['icon'] ?? 'ki-notification' }} fs-3"></i>
                    </div>
                    <div>
                        <h3 class="nc-body-title">
                            @if (! $notification->read_at)<span class="nc-dot me-1"></span>@endif
                            {{ $data['title'] ?? '' }}
                        </h3>
                        <p class="nc-body-text">{{ $data['body'] ?? '' }}</p>
                        <div class="nc-tags">
                            <span class="nc-tag">@lang('general::notifications.module_'.$module)</span>
                            <span class="nc-tag sev-{{ $sev }}">@lang('general::notifications.severity_'.$sev)</span>
                        </div>
                    </div>
                    <div class="nc-side">
                        <div class="nc-time">{{ $notification->created_at?->diffForHumans() }}</div>
                        <div class="nc-actions">
                            <button type="button" class="btn btn-icon btn-sm btn-light notif-delete-one" data-id="{{ $notification->id }}" title="@lang('employee::fields.delete')">
                                <i class="ki-outline ki-trash fs-5"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="nc-empty">
                    <div class="mb-3"><i class="ki-outline ki-notification-on"></i></div>
                    <div class="fw-semibold text-gray-700 mb-1">@lang('general.no_notifications')</div>
                </div>
            @endforelse
        </div>

        <div class="mt-5 d-flex justify-content-center">{{ $notifications->links() }}</div>
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    function post(url, data) {
        return $.ajax({
            url: url,
            method: 'POST',
            data: Object.assign({ _token: '{{ csrf_token() }}' }, data || {})
        });
    }

    $('#notif-center-mark-all').on('click', function () {
        post('{{ route('notification-mark-all-as-read') }}').done(function () {
            window.location.reload();
        });
    });

    $(document).on('click', '.notif-open-row', function (e) {
        if ($(e.target).closest('.notif-delete-one').length) return;
        const id = $(this).data('id');
        const href = $(this).data('url');
        post('{{ route('notification-mark-as-read') }}', { id: id }).done(function (res) {
            if (href) window.location.href = href;
            else if (res.url) window.location.href = res.url;
            else window.location.reload();
        });
    });

    $(document).on('click', '.notif-delete-one', function (e) {
        e.stopPropagation();
        const id = $(this).data('id');
        post('{{ route('notification-delete') }}', { id: id }).done(function () {
            window.location.reload();
        });
    });
})();
</script>
@endsection
