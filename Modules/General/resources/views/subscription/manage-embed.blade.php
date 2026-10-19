@extends('layouts.app')

@section('title', __('general::general.subscription_manage_title'))

@section('css')
    <style>
        .sub-manage-embed {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            min-height: calc(100vh - 220px);
        }
        .sub-manage-embed__toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }
        .sub-manage-embed__title {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: #1a1a1a;
        }
        .sub-manage-embed__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .sub-manage-embed__frame-wrap {
            position: relative;
            flex: 1 1 auto;
            min-height: 720px;
            border: 1px solid rgba(26, 26, 26, 0.08);
            border-radius: 1rem;
            overflow: hidden;
            background: #f7f4eb;
            box-shadow: 0 16px 40px rgba(26, 26, 26, 0.07);
        }
        .sub-manage-embed__frame {
            display: block;
            width: 100%;
            min-height: 720px;
            height: 720px;
            border: 0;
            background: #f7f4eb;
        }
        .sub-manage-embed__loading {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            background: rgba(247, 244, 235, 0.92);
            color: #6b7280;
            font-weight: 700;
            z-index: 2;
            transition: opacity .2s ease;
        }
        .sub-manage-embed__loading.is-hidden {
            opacity: 0;
            pointer-events: none;
        }
    </style>
@endsection

@section('content')
    <div class="sub-manage-embed" id="subscription_manage_embed_root"
         data-handoff-url="{{ $handoffUrl }}">
        <div class="sub-manage-embed__toolbar">
            <div>
                <h1 class="sub-manage-embed__title">@lang('general::general.subscription_manage_title')</h1>
                <div class="text-muted fs-7">@lang('general::general.subscription_manage_embed_hint')</div>
            </div>
            <div class="sub-manage-embed__actions">
                <a href="{{ $backUrl }}" class="btn btn-light">@lang('general::general.subscription_manage_back')</a>
                <button type="button" class="btn btn-light-primary" id="subscription_manage_reload">
                    @lang('general::general.subscription_manage_reload')
                </button>
            </div>
        </div>

        <div class="sub-manage-embed__frame-wrap">
            <div class="sub-manage-embed__loading" id="subscription_manage_loading">
                @lang('general::general.subscription_manage_loading')
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
            var loading = document.getElementById('subscription_manage_loading');
            var reloadBtn = document.getElementById('subscription_manage_reload');
            if (!root || !frame) return;

            var handoffUrl = root.getAttribute('data-handoff-url');

            function hideLoading() {
                if (loading) loading.classList.add('is-hidden');
            }

            function showLoading() {
                if (loading) loading.classList.remove('is-hidden');
            }

            function setFrameHeight(height) {
                var px = Math.max(720, Number(height) || 720);
                frame.style.height = px + 'px';
                frame.style.minHeight = px + 'px';
            }

            frame.addEventListener('load', hideLoading);

            window.addEventListener('message', function (event) {
                var data = event.data || {};
                if (!data || typeof data !== 'object') return;

                if (data.type === 'subscribe-embed-height' && data.height) {
                    setFrameHeight(data.height);
                    hideLoading();
                }

                if (data.type === 'subscribe-embed-reload') {
                    window.location.reload();
                }
            });

            if (reloadBtn) {
                reloadBtn.addEventListener('click', function () {
                    showLoading();
                    window.location.reload();
                });
            }

            // Safety: if the handoff URL somehow became a bare /subscribe without token, do not keep looping.
            if (handoffUrl && /\/subscribe\/?(\?|$)/.test(handoffUrl) && handoffUrl.indexOf('/subscribe/handoff/') === -1) {
                window.location.href = handoffUrl;
            }
        })();
    </script>
@endsection
