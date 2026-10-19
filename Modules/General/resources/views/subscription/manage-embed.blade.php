@extends('layouts.app')

@section('title', __('general::general.subscription_manage_title'))

@section('css')
    <style>
        .sub-manage-embed {
            display: flex;
            flex-direction: column;
            margin: -0.25rem 0 0;
            min-height: calc(100vh - 160px);
            height: calc(100vh - 160px);
        }
        .sub-manage-embed__frame-wrap {
            position: relative;
            flex: 1 1 auto;
            min-height: 0;
            height: 100%;
            border: 0;
            border-radius: 0.85rem;
            overflow: hidden;
            background: #f7f4eb;
        }
        .sub-manage-embed__frame {
            display: block;
            width: 100%;
            height: 100%;
            min-height: 100%;
            border: 0;
            background: #f7f4eb;
        }
        .sub-manage-embed__overlay {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            background: rgba(247, 244, 235, 0.96);
            color: #6b7280;
            font-weight: 700;
            z-index: 2;
            padding: 1.25rem;
            text-align: center;
            transition: opacity .2s ease;
        }
        .sub-manage-embed__overlay.is-hidden {
            opacity: 0;
            pointer-events: none;
        }
        .sub-manage-embed__overlay-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.85rem;
            max-width: 360px;
        }
        .sub-manage-embed__fail-title {
            margin: 0;
            color: #1a1a1a;
            font-size: 1.05rem;
            font-weight: 800;
        }
        .sub-manage-embed__fail-text {
            margin: 0;
            color: #6b7280;
            font-weight: 600;
            line-height: 1.55;
        }
    </style>
@endsection

@section('content')
    <div class="sub-manage-embed" id="subscription_manage_embed_root"
         data-handoff-url="{{ $handoffUrl }}">
        <div class="sub-manage-embed__frame-wrap">
            <div class="sub-manage-embed__overlay" id="subscription_manage_loading" data-state="loading">
                <div class="sub-manage-embed__overlay-inner">
                    <div id="subscription_manage_loading_text">@lang('general::general.subscription_manage_loading')</div>
                    <div id="subscription_manage_fail_block" class="d-none">
                        <p class="sub-manage-embed__fail-title">@lang('general::general.subscription_manage_load_failed_title')</p>
                        <p class="sub-manage-embed__fail-text">@lang('general::general.subscription_manage_load_failed_body')</p>
                        <button type="button" class="btn btn-primary" id="subscription_manage_reload">
                            @lang('general::general.subscription_manage_reload')
                        </button>
                    </div>
                </div>
            </div>
            <iframe
                id="subscription_manage_frame"
                class="sub-manage-embed__frame"
                title="{{ __('general::general.subscription_manage_title') }}"
                src="{{ $handoffUrl }}"
                referrerpolicy="no-referrer-when-downgrade"
                allow="payment *"
            ></iframe>
        </div>
    </div>
@endsection

@section('script')
    @parent
    <script>
        (function () {
            var root = document.getElementById('subscription_manage_embed_root');
            var frame = document.getElementById('subscription_manage_frame');
            var overlay = document.getElementById('subscription_manage_loading');
            var loadingText = document.getElementById('subscription_manage_loading_text');
            var failBlock = document.getElementById('subscription_manage_fail_block');
            var reloadBtn = document.getElementById('subscription_manage_reload');
            if (!root || !frame || !overlay) return;

            var handoffUrl = root.getAttribute('data-handoff-url');
            var ready = false;
            var failTimer = null;

            function hideOverlay() {
                ready = true;
                if (failTimer) {
                    clearTimeout(failTimer);
                    failTimer = null;
                }
                overlay.classList.add('is-hidden');
                overlay.setAttribute('data-state', 'ready');
            }

            function showLoading() {
                ready = false;
                overlay.classList.remove('is-hidden');
                overlay.setAttribute('data-state', 'loading');
                if (loadingText) loadingText.classList.remove('d-none');
                if (failBlock) failBlock.classList.add('d-none');
                armFailTimer();
            }

            function showFail() {
                if (ready) return;
                overlay.classList.remove('is-hidden');
                overlay.setAttribute('data-state', 'failed');
                if (loadingText) loadingText.classList.add('d-none');
                if (failBlock) failBlock.classList.remove('d-none');
            }

            function armFailTimer() {
                if (failTimer) clearTimeout(failTimer);
                failTimer = setTimeout(function () {
                    if (!ready) showFail();
                }, 15000);
            }

            frame.addEventListener('load', function () {
                // Keep waiting for a height ping from the embed; bare load can be an error page.
                // If height arrives we hide. Fail timer handles stuck states.
            });

            window.addEventListener('message', function (event) {
                var data = event.data || {};
                if (!data || typeof data !== 'object') return;

                if (data.type === 'subscribe-embed-height' && data.height) {
                    hideOverlay();
                }

                if (data.type === 'subscribe-embed-reload') {
                    showLoading();
                    window.location.reload();
                }

                if (data.type === 'subscribe-embed-failed') {
                    showFail();
                }
            });

            if (reloadBtn) {
                reloadBtn.addEventListener('click', function () {
                    showLoading();
                    window.location.reload();
                });
            }

            if (handoffUrl && /\/subscribe\/?(\?|$)/.test(handoffUrl) && handoffUrl.indexOf('/subscribe/handoff/') === -1) {
                window.location.href = handoffUrl;
                return;
            }

            armFailTimer();
        })();
    </script>
@endsection
