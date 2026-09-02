@extends('screen::layouts.master')

@section('title', __('menuItemLang.screen_module'))

@section('css')
    <style>
        :root {
            --screen-accent: #3699ff;
            --screen-accent-2: #8950fc;
            --screen-success: #50cd89;
            --screen-surface: #f5f8fa;
            --screen-border: #e4e6ef;
            --screen-text-muted: #7e8299;
            --screen-shadow: 0 10px 35px rgba(82, 63, 105, 0.08);
            --screen-shadow-hover: 0 16px 40px rgba(82, 63, 105, 0.12);
        }

        .screen-hero {
            border-radius: 16px;
            padding: 1.5rem 1.75rem;
            background: linear-gradient(180deg, #f3f4f7 0%, #eceef2 100%);
            border: 1px solid #e0e3ea;
            color: #3f4254;
            box-shadow: 0 4px 24px rgba(24, 28, 50, 0.04);
            position: relative;
            overflow: hidden;
        }

        .screen-hero h1 {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0 0 0.35rem;
            color: #181c32;
        }

        .screen-hero p {
            margin: 0;
            font-size: 0.95rem;
            max-width: 42rem;
            line-height: 1.55;
            color: #7e8299;
        }

        .screen-kpi-grid .screen-kpi-card {
            border: 1px solid var(--screen-border);
            border-radius: 14px;
            padding: 1.1rem 1.15rem 1.15rem;
            background: #fff;
            box-shadow: var(--screen-shadow);
            height: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .screen-kpi-grid .screen-kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: 14px 14px 0 0;
        }

        .screen-kpi-grid .screen-kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--screen-shadow-hover);
        }

        .screen-kpi-card--promos::before,
        .screen-kpi-card--playlists::before,
        .screen-kpi-card--devices::before {
            background: linear-gradient(90deg, #c5cad6, #aeb4c2);
        }

        .screen-kpi-inner {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
        }

        .screen-kpi-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .screen-kpi-card--promos .screen-kpi-icon,
        .screen-kpi-card--playlists .screen-kpi-icon,
        .screen-kpi-card--devices .screen-kpi-icon {
            background: #e4e6ef;
            color: #5e6278;
        }

        .screen-kpi-title {
            color: var(--screen-text-muted);
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.25rem;
        }

        .screen-kpi-value {
            font-size: 1.65rem;
            font-weight: 800;
            color: #181c32;
            line-height: 1.15;
            letter-spacing: -0.02em;
        }

        .screen-workspace {
            display: flex;
            flex-direction: row;
            gap: 1.25rem;
            align-items: stretch;
        }

        @media (max-width: 991.98px) {
            .screen-workspace {
                flex-direction: column;
            }
        }

        .screen-subtabs {
            border: 1px solid var(--screen-border);
            border-radius: 16px;
            background: #fff;
            box-shadow: var(--screen-shadow);
            padding: 0.65rem;
            min-width: 220px;
        }

        @media (min-width: 992px) {
            .screen-subtabs {
                max-width: 240px;
            }
        }

        .screen-subtabs .nav-link {
            border-radius: 12px;
            margin-bottom: 4px;
            font-weight: 600;
            font-size: 0.92rem;
            color: #3f4254;
            padding: 0.75rem 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            border: 1px solid transparent;
            transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        }

        .screen-subtabs .nav-link i {
            width: 1.25rem;
            text-align: center;
            opacity: 0.85;
            font-size: 1rem;
        }

        .screen-subtabs .nav-link:hover {
            background: var(--screen-surface);
            color: #181c32;
        }

        .screen-subtabs .nav-link.active {
            background: #e9ecf2;
            color: #181c32;
            border-color: #d8dce6;
        }

        .screen-content-shell {
            flex: 1;
            min-width: 0;
            border: 1px solid var(--screen-border);
            border-radius: 16px;
            background: #fff;
            box-shadow: var(--screen-shadow);
            overflow: hidden;
            min-height: 480px;
        }

        .screen-content-shell .tab-content {
            min-height: 400px;
        }

        .upload-progress {
            height: 20px;
            margin-bottom: 20px;
            overflow: hidden;
            background-color: #f5f5f5;
            border-radius: 4px;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, .1);
        }

        .upload-progress-bar {
            width: 0%;
            height: 100%;
            font-size: 12px;
            line-height: 20px;
            color: #fff;
            text-align: center;
            background-color: #007bff;
            transition: width .6s ease;
        }

        .screen-migration-alert {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 18px rgba(241, 65, 108, 0.12);
        }

        .screen-tab-pane {
            padding: 1.75rem 1.75rem 2.25rem;
        }

        @media (min-width: 992px) {
            .screen-tab-pane {
                padding: 2rem 2.25rem 2.75rem;
            }
        }

        .screen-tab-header {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.35rem;
            padding-bottom: 1.15rem;
            border-bottom: 1px solid var(--screen-border);
        }

        .screen-tab-header h2 {
            font-size: 1.12rem;
            font-weight: 700;
            margin: 0 0 0.3rem;
            color: #181c32;
            letter-spacing: -0.02em;
        }

        .screen-tab-header .screen-tab-desc {
            margin: 0;
            font-size: 0.9rem;
            color: var(--screen-text-muted);
            max-width: 40rem;
            line-height: 1.55;
        }

        .screen-tab-header .btn-primary {
            box-shadow: 0 4px 14px rgba(24, 28, 50, 0.08);
            border-radius: 10px;
            font-weight: 600;
            padding-inline: 1.15rem;
        }

        .screen-table-card {
            border: 1px solid var(--screen-border);
            border-radius: 14px;
            background: var(--screen-surface);
            padding: 0.35rem;
        }

        .screen-table-card .table-responsive,
        .screen-table-card table {
            margin-bottom: 0;
        }

        .screen-table-card table thead th {
            background: #fff;
            color: #3f4254;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom-width: 1px;
        }
    </style>
    @if (session('locale') === 'ar')
        <style>
            .upload-progress-bar {
                float: right;
            }
        </style>
    @else
        <style>
            .upload-progress-bar {
                float: left;
            }
        </style>
    @endif

@endsection
@section('content')
    @php
        $screenCanPromos = dashboard_can(\Modules\Screen\Support\ScreenPermissions::for('promos', 'show'));
        $screenCanPlaylists = dashboard_can(\Modules\Screen\Support\ScreenPermissions::for('playlists', 'show'));
        $screenCanDevices = dashboard_can(\Modules\Screen\Support\ScreenPermissions::for('devices', 'show'));
        $screenPromosTabActive = $screenCanPromos;
        $screenPlaylistsTabActive = ! $screenCanPromos && $screenCanPlaylists;
        $screenDevicesTabActive = ! $screenCanPromos && ! $screenCanPlaylists && $screenCanDevices;
    @endphp
    <div class="d-flex flex-column flex-row-fluid gap-5">
        <div class="screen-hero">
            <h1>{{ __('menuItemLang.screen_module') }}</h1>
            <p>{{ __('screen::general.dashboard_hero_subtitle') }}</p>
        </div>

        <div class="row g-4 screen-kpi-grid">
            @if ($screenCanPromos)
                <div class="col-md-4">
                    <div class="screen-kpi-card screen-kpi-card--promos">
                        <div class="screen-kpi-inner">
                            <div class="screen-kpi-icon" aria-hidden="true">
                                <i class="fas fa-photo-video"></i>
                            </div>
                            <div>
                                <div class="screen-kpi-title">{{ __('screen::general.promos') }}</div>
                                <div class="screen-kpi-value">{{ $promos->count() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if ($screenCanPlaylists)
                <div class="col-md-4">
                    <div class="screen-kpi-card screen-kpi-card--playlists">
                        <div class="screen-kpi-inner">
                            <div class="screen-kpi-icon" aria-hidden="true">
                                <i class="fas fa-list-ol"></i>
                            </div>
                            <div>
                                <div class="screen-kpi-title">{{ __('screen::general.playlists') }}</div>
                                <div class="screen-kpi-value">{{ $playlistsCount ?? 0 }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if ($screenCanDevices)
                <div class="col-md-4">
                    <div class="screen-kpi-card screen-kpi-card--devices">
                        <div class="screen-kpi-inner">
                            <div class="screen-kpi-icon" aria-hidden="true">
                                <i class="fas fa-tv"></i>
                            </div>
                            <div>
                                <div class="screen-kpi-title">{{ __('screen::general.devices') }}</div>
                                <div class="screen-kpi-value">{{ $devices->count() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="screen-workspace">
            <ul class="nav nav-tabs nav-pills flex-row flex-md-column flex-nowrap flex-md-wrap mb-0 fs-6 screen-subtabs"
                role="tablist">
                @if ($screenCanPromos)
                    <li class="nav-item w-100 me-0" role="presentation">
                        <a class="nav-link py-3 {{ $screenPromosTabActive ? 'active' : '' }}" data-bs-toggle="tab"
                            href="#promos_tab" role="tab" aria-selected="{{ $screenPromosTabActive ? 'true' : 'false' }}">
                            <i class="fas fa-photo-video"></i>
                            <span>@lang('screen::general.promos')</span>
                        </a>
                    </li>
                @endif
                @if ($screenCanPlaylists)
                    <li class="nav-item w-100 me-0" role="presentation">
                        <a class="nav-link py-3 {{ $screenPlaylistsTabActive ? 'active' : '' }}" data-bs-toggle="tab"
                            href="#playlists_tab" role="tab"
                            aria-selected="{{ $screenPlaylistsTabActive ? 'true' : 'false' }}">
                            <i class="fas fa-stream"></i>
                            <span>@lang('screen::general.playlists')</span>
                        </a>
                    </li>
                @endif
                @if ($screenCanDevices)
                    <li class="nav-item w-100 me-0" role="presentation">
                        <a class="nav-link py-3 {{ $screenDevicesTabActive ? 'active' : '' }}" data-bs-toggle="tab"
                            href="#devices_tab" role="tab"
                            aria-selected="{{ $screenDevicesTabActive ? 'true' : 'false' }}">
                            <i class="fas fa-desktop"></i>
                            <span>@lang('screen::general.devices')</span>
                        </a>
                    </li>
                @endif
            </ul>
            <div class="screen-content-shell flex-grow-1">
                @if (empty($hasEstablishmentColumn))
                    <div class="alert alert-warning screen-migration-alert m-5 mb-0">
                        {{ app()->getLocale() === 'ar'
                            ? 'تم تشغيل توافق مؤقت للأجهزة. لتفعيل الربط الكامل بالأفرع يرجى تنفيذ الترحيل (migrate).'
                            : 'Temporary compatibility mode is active. Run migrations to enable full branch-device linking.' }}
                    </div>
                @endif
                <div class="tab-content w-100 p-0" id="mySubTabContent">
                    @if ($screenCanPromos)
                        <x-screen::promo.promo-tab :active="$screenPromosTabActive" />
                    @endif
                    @if ($screenCanPlaylists)
                        <x-screen::playlist.playlist-tab :active="$screenPlaylistsTabActive" />
                    @endif
                    @if ($screenCanDevices)
                        <x-screen::device.device-tab :active="$screenDevicesTabActive" />
                    @endif
                </div>
            </div>
        </div>
    </div>
    <x-screen::promo.add-promo-modal />
    <x-screen::promo.rename-promo-modal />
    <x-screen::promo.preview-promo-modal />
    <x-screen::playlist.add-playlist-modal :establishments="$establishments" :devices="$devices" />
    <x-screen::playlist.preview-playlist-modal />
    <x-screen::device.add-device-modal :establishments="$establishments" />

@endsection

@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>

    <script type="text/javascript" src="/vfs_fonts.js"></script>
    <script>
        "use strict";
        let request;
        let promoDataTable;
        var selectedInOrder = [];
        const promoTable = $('#promo_table');
        const promoDataUrl = '{{ route('promos.index') }}';
        let promoPlaylistDataTable;
        const promoPlaylistTable = $('#promo_Playlist_table');
        const promoPlaylistDataUrl = '{{ route('promos.playlist-index') }}';
        let playlistDataTable;
        const playlistTable = $('#playlist_table');
        const playlistDataUrl = '{{ route('playlists.index') }}';
        let DeviceDataTable;
        const deviceTable = $('#device_table');
        const deviceDataUrl = '{{ route('devices.index') }}';

        $(document).ready(function() {
            @if ($screenCanPromos)
                initPromoDataTable();
                renameModal();
                addPromoModal();
                promoTab();
            @endif
            @if ($screenCanPlaylists)
                initializeStyles();
                initializeModal();
                playlistTab();
                addPlaylistModal();
                addPlaylistForm();
                initPlaylistDataTable();
                previewPlaylistModal();
            @endif
            @if ($screenCanDevices)
                initDeviceDataTable();
                addDeviceModal();
            @endif
        });
    </script>
@endsection
