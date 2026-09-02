@php
    $copy = \App\Support\HttpErrorPage::forbiddenCopy($exception ?? null);
    $loginUrl = \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login');
@endphp
@extends('errors.layout')

@section('title', __('errors.forbidden_document_title').' — '.brand_short_name())

@section('content')
    <article class="err-card" role="alert">
        <div class="err-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <rect x="5" y="11" width="14" height="10" rx="2.2"></rect>
                <path d="M8 11V8.2a4 4 0 0 1 8 0V11"></path>
                <circle cx="12" cy="16" r="1.05" fill="currentColor" stroke="none"></circle>
            </svg>
        </div>
        <p class="err-kicker">@lang('errors.forbidden_kicker')</p>
        <h1 class="err-title">{{ $copy['title'] }}</h1>
        <p class="err-body">{{ $copy['body'] }}</p>
        <p class="err-hint">{{ $copy['hint'] }}</p>

        <div class="err-actions">
            <button type="button" class="err-btn err-btn--primary" data-err-back>
                @lang('errors.go_back')
            </button>
        </div>
    </article>
@endsection

@section('scripts')
    <script>
        (function () {
            var back = document.querySelector('[data-err-back]');
            if (!back) return;
            var fallback = @json($loginUrl);
            back.addEventListener('click', function () {
                if (window.history.length > 1 && document.referrer && document.referrer !== window.location.href) {
                    window.history.back();
                    return;
                }
                window.location.href = fallback;
            });
        })();
    </script>
@endsection
