@extends('screen::layouts.master')

@section('title', __('menuItemLang.screen_module'))

@section('css')
    <style>
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
    <div class="d-flex flex-column flex-row-fluid gap-5">
        <div class="d-flex flex-row-fluid gap-5">
            <ul
                class="nav nav-tabs nav-pills rounded shadow-sm p-5 flex-row flex-md-column me-5 mb-3 mb-md-0 fs-6 min-h-750px">
                <li class="nav-item w-md-200px me-0">
                    <a class="nav-link py-3 active" data-bs-toggle="tab" href="#promos_tab">
                        @lang('screen::general.promos')
                    </a>
                </li>
                <li class="nav-item w-md-200px me-0">
                    <a class="nav-link py-3" data-bs-toggle="tab" href="#playlists_tab">
                        @lang('screen::general.playlists')
                    </a>
                </li>
                <li class="nav-item w-md-200px me-0">
                    <a class="nav-link py-3" data-bs-toggle="tab" href="#devices_tab">
                        @lang('screen::general.devices')
                    </a>
                </li>

            </ul>
            <div class="tab-content w-100" id="mySubTabContent">
                <x-screen::promo.promo-tab />
                <x-screen::playlist.playlist-tab />
                <x-screen::device.device-tab />
            </div>
        </div>
    </div>
    <x-screen::promo.add-promo-modal />
    <x-screen::promo.rename-promo-modal />
    <x-screen::promo.preview-promo-modal />
    <x-screen::playlist.add-playlist-modal :establishments="$establishments" :devices="$devices" />
    <x-screen::playlist.preview-playlist-modal />
    <x-screen::device.add-device-modal />

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
            initPromoDataTable();
            initDeviceDataTable();
            playlistTab();
            initializeStyles();
            initializeModal();
            renameModal();
            addPromoModal();
            promoTab();
            addPlaylistModal();
            addPlaylistForm();
            initPlaylistDataTable();
            addDeviceModal();
            previewPlaylistModal()
        });
    </script>
@endsection
