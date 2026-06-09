@extends('layouts.app')
@section('title', __('franchise::lang.franchise'))

@section('css')
    <style>
        .franchise-hub-shell { min-height: 0; }
        .franchise-hub-tabs {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #eef1f7;
            padding: 6px 8px;
            box-shadow: 0 2px 12px rgba(62, 57, 107, 0.05);
            gap: 4px;
            flex-wrap: nowrap;
            overflow-x: auto;
            scrollbar-width: thin;
        }
        .franchise-hub-tabs .nav-link {
            white-space: nowrap;
            border-radius: 8px;
            color: #5e6278;
            font-weight: 600;
            font-size: 13px;
            padding: 8px 14px;
            border: 0;
            margin: 0;
        }
        .franchise-hub-tabs .nav-link.active {
            background: var(--bs-primary-light);
            color: var(--bs-primary);
        }
        .franchise-hub-tabs .nav-link:not(.active):hover {
            background: #f8f9fc;
            color: #181c32;
        }
        .franchise-hub-panel,
        #franchise-hub-embed-wrap {
            overflow: visible;
        }
        #franchise-hub-iframe {
            width: 100%;
            height: 0;
            min-height: 0;
            border: 0;
            border-radius: 14px;
            background: #f5f8fa;
            display: none;
            overflow: hidden;
            vertical-align: top;
        }
        #franchise-hub-iframe.is-visible { display: block; }
        .franchise-hub-loading {
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-3 franchise-hub-shell">
        <nav class="nav nav-pills franchise-hub-tabs mb-4" id="franchiseHubTabs" role="tablist">
            @foreach ($franchiseTabs as $tab)
                @php
                    $labelKey = $tab['label'];
                    $tabLabel = __($labelKey);
                    if ($tabLabel === $labelKey) {
                        $tabLabel = $tab['id'];
                    }
                @endphp
                <button type="button"
                    class="nav-link {{ $activeFranchiseTab === $tab['id'] ? 'active' : '' }}"
                    data-franchise-tab="{{ $tab['id'] }}"
                    data-tab-type="{{ $tab['type'] }}"
                    data-embed-url="{{ $tab['embed_url'] ?? '' }}"
                    role="tab"
                    aria-selected="{{ $activeFranchiseTab === $tab['id'] ? 'true' : 'false' }}">
                    <i class="{{ $tab['icon'] }} me-1 opacity-75"></i>{{ $tabLabel }}
                </button>
            @endforeach
        </nav>

        <div class="franchise-hub-panel">
            <div id="franchise-hub-companies-wrap" class="{{ $activeFranchiseTab === 'companies' ? '' : 'd-none' }}">
                @include('franchise::companies.partials.list')
            </div>
            <div id="franchise-hub-embed-wrap" class="{{ $activeFranchiseTab === 'companies' ? 'd-none' : '' }}">
                <div id="franchise-hub-loading" class="franchise-hub-loading {{ $activeFranchiseTab === 'companies' ? 'd-none' : '' }}">
                    <span class="spinner-border text-primary" role="status"></span>
                </div>
                <iframe id="franchise-hub-iframe"
                    title="{{ __('franchise::lang.franchise') }}"
                    scrolling="no"
                    class="{{ $activeFranchiseTab === 'companies' ? '' : 'is-visible' }}"></iframe>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        window.franchiseHubConfig = {
            activeTab: @json($activeFranchiseTab),
            tabs: @json(collect($franchiseTabs)->mapWithKeys(fn ($t) => [$t['id'] => [
                'type' => $t['type'],
                'embed_url' => $t['embed_url'] ?? null,
            ]]))
        };
    </script>
    <script src="{{ asset('js/franchise-hub.js') }}?v=3"></script>
    @include('franchise::companies.partials.list-scripts')
@endsection
